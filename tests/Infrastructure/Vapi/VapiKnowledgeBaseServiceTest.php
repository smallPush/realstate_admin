<?php

namespace App\Tests\Infrastructure\Vapi;

use App\Domain\Apartment\ApartmentRepositoryInterface;
use App\Infrastructure\Vapi\VapiKnowledgeBaseService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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

    public function testStartDeletingPreviousFileHandlesException(): void
    {
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $apartmentRepositoryMock = $this->createMock(ApartmentRepositoryInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $shareDir = sys_get_temp_dir() . '/vapi_test_' . uniqid();
        if (!is_dir($shareDir)) {
            mkdir($shareDir, 0777, true);
        }
        $fileIdPath = $shareDir . '/vapi_file_id.txt';
        file_put_contents($fileIdPath, 'old-file-id');

        $apartmentRepositoryMock->expects($this->once())
            ->method('findAvailable')
            ->willReturn([]);

        $exception = new \Exception('Network error during DELETE');

        $httpClientMock->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function(string $method, string $url) use ($exception) {
                if ($method === 'DELETE') {
                    throw $exception;
                }

                $responseMock = $this->createMock(ResponseInterface::class);
                $responseMock->method('toArray')->willReturn(['id' => 'new-file-id']);
                return $responseMock;
            });

        // We expect warning about deletion failure.
        $loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'Vapi: could not start deleting previous file: {error}',
                ['error' => 'Network error during DELETE']
            );

        $service = new VapiKnowledgeBaseService(
            $httpClientMock,
            $apartmentRepositoryMock,
            $loggerMock,
            'real-api-key',
            $shareDir,
            'https://api.vapi.ai'
        );

        $service->syncKnowledgeBase();

        @unlink($fileIdPath);
        @rmdir($shareDir);
    }
}
