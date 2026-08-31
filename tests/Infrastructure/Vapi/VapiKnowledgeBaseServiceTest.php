<?php

namespace App\Tests\Infrastructure\Vapi;

use App\Domain\Apartment\ApartmentRepositoryInterface;
use App\Infrastructure\Vapi\VapiKnowledgeBaseService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VapiKnowledgeBaseServiceTest extends TestCase
{
    public function testSyncKnowledgeBaseThrowsIfApiKeyIsEmpty(): void
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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('VAPI_API_KEY is not configured.');

        $service->syncKnowledgeBase();
    }

    public function testSyncKnowledgeBaseLogsWarningOnDeleteException(): void
    {
        $apartmentRepositoryMock = $this->createMock(ApartmentRepositoryInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $shareDir = sys_get_temp_dir() . '/vapi_test_' . uniqid();
        mkdir($shareDir);
        file_put_contents($shareDir . '/vapi_file_id.txt', 'dummy_file_id');

        $apartmentRepositoryMock->expects($this->once())
            ->method('findAvailable')
            ->willReturn([]);

        $httpClient = new MockHttpClient(static function (string $method): MockResponse {
            if ($method === 'DELETE') {
                throw new \Exception('Delete failed');
            }

            return new MockResponse(json_encode(['id' => 'new_file_id'], JSON_THROW_ON_ERROR));
        });

        $httpClientMock->method('stream')
            ->willReturnCallback(function ($responses) {
                $generator = function () use ($responses) {
                    foreach ($responses as $response) {
                        $chunk = $this->createMock(\Symfony\Contracts\HttpClient\ChunkInterface::class);
                        $chunk->method('isLast')->willReturn(true);
                        yield $response => $chunk;
                    }
                };
                return new \Symfony\Component\HttpClient\Response\ResponseStream($generator());
            });

        $loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'Vapi: could not start deleting previous file: {error}',
                ['error' => 'Delete failed']
            );

        $service = new VapiKnowledgeBaseService(
            $httpClient,
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
