<?php

namespace App\Tests\Infrastructure\Vapi;

use App\Domain\Apartment\ApartmentRepositoryInterface;
use App\Infrastructure\Vapi\VapiKnowledgeBaseService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VapiKnowledgeBaseServiceTest extends TestCase
{
    public function testSyncKnowledgeBaseSkipsIfApiKeyIsEmpty(): void
    {
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $apartmentRepositoryMock = $this->createMock(ApartmentRepositoryInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $loggerMock->expects($this->once())
            ->method('warning')
            ->with('VAPI_API_KEY is not configured or is a placeholder — skipping Knowledge Base sync.');

        $service = new VapiKnowledgeBaseService(
            $httpClientMock,
            $apartmentRepositoryMock,
            $loggerMock,
            '',
            '/tmp',
            'https://api.vapi.ai'
        );

        $service->syncKnowledgeBase();
    }

    public function testSyncKnowledgeBaseLogsWarningOnDeleteException(): void
    {
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $apartmentRepositoryMock = $this->createMock(ApartmentRepositoryInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $shareDir = sys_get_temp_dir() . '/vapi_test_' . uniqid();
        mkdir($shareDir);
        file_put_contents($shareDir . '/vapi_file_id.txt', 'dummy_file_id');

        $apartmentRepositoryMock->expects($this->once())
            ->method('findAvailable')
            ->willReturn([]);

        $httpClientMock->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function(string $method, string $url, array $options) {
                if ($method === 'DELETE') {
                    throw new \Exception('Delete failed');
                }
                if ($method === 'POST') {
                    $responseMock = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
                    $responseMock->method('toArray')->willReturn(['id' => 'new_file_id']);
                    return $responseMock;
                }

                return $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
            });

        $loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'Vapi: could not start deleting previous file: {error}',
                ['error' => 'Delete failed']
            );

        $service = new VapiKnowledgeBaseService(
            $httpClientMock,
            $apartmentRepositoryMock,
            $loggerMock,
            'valid_api_key',
            $shareDir,
            'https://api.vapi.ai'
        );

        try {
            $service->syncKnowledgeBase();
        } finally {
            if (file_exists($shareDir . '/vapi_file_id.txt')) {
                unlink($shareDir . '/vapi_file_id.txt');
            }
            if (is_dir($shareDir)) {
                rmdir($shareDir);
            }
        }
    }
}
