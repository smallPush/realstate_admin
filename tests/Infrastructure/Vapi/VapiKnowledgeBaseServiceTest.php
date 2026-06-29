<?php

namespace App\Tests\Infrastructure\Vapi;

use App\Domain\Apartment\ApartmentRepositoryInterface;
use App\Infrastructure\Vapi\VapiKnowledgeBaseService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class VapiKnowledgeBaseServiceTest extends TestCase
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

    public function testSyncKnowledgeBaseWithEmptyApartments(): void
    {
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $apartmentRepositoryMock = $this->createMock(ApartmentRepositoryInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);

        $apartmentRepositoryMock->expects($this->once())
            ->method('findAvailable')
            ->willReturn([]);

        $apartmentRepositoryMock->expects($this->once())
            ->method('saveAll')
            ->with([]);

        $responseMock->expects($this->once())
            ->method('toArray')
            ->willReturn(['id' => 'file-123']);

        $httpClientMock->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.vapi.ai/file',
                $this->callback(function (array $options) {
                    $bodyIterable = $options['body'];
                    $content = '';
                    foreach ($bodyIterable as $chunk) {
                        $content .= $chunk;
                    }
                    return str_contains($content, 'Total de apartamentos disponibles: 0');
                })
            )
            ->willReturn($responseMock);

        $tmpShareDir = sys_get_temp_dir() . '/vapi_test_' . uniqid();
        mkdir($tmpShareDir, 0777, true);

        $service = new VapiKnowledgeBaseService(
            $httpClientMock,
            $apartmentRepositoryMock,
            $loggerMock,
            'fake-api-key',
            $tmpShareDir,
            'https://api.vapi.ai'
        );

        $service->syncKnowledgeBase();

        // Cleanup
        if (file_exists($tmpShareDir . '/vapi_file_id.txt')) {
            unlink($tmpShareDir . '/vapi_file_id.txt');
        }
        rmdir($tmpShareDir);
    }
}
