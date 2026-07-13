<?php

namespace App\Tests\Infrastructure\Vapi;

use App\Domain\Apartment\VapiAssistantConfig;
use App\Infrastructure\Vapi\VapiAssistantService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class VapiAssistantServiceTest extends TestCase
{
    public function testSyncAssistantThrowsExceptionIfApiKeyIsEmpty(): void
    {
        $httpClientMock = $this->createStub(HttpClientInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $loggerMock->expects($this->once())
            ->method('warning')
            ->with('VAPI_API_KEY is not configured or is a placeholder — skipping Assistant sync.');

        $service = new VapiAssistantService(
            $httpClientMock,
            $loggerMock,
            '',
            'https://api.vapi.ai',
            'assistant_id'
        );

        $config = $this->createStub(VapiAssistantConfig::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('VAPI_API_KEY is not configured.');

        $service->syncAssistant($config);
    }

    public function testSyncAssistantThrowsExceptionIfApiKeyIsPlaceholder(): void
    {
        $httpClientMock = $this->createStub(HttpClientInterface::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $loggerMock->expects($this->once())
            ->method('warning')
            ->with('VAPI_API_KEY is not configured or is a placeholder — skipping Assistant sync.');

        $service = new VapiAssistantService(
            $httpClientMock,
            $loggerMock,
            'your_vapi_api_key_here',
            'https://api.vapi.ai',
            null
        );

        $config = $this->createStub(VapiAssistantConfig::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('VAPI_API_KEY is not configured.');

        $service->syncAssistant($config);
    }
}
