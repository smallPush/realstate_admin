<?php

namespace App\Tests\Infrastructure\Vapi;

use App\Domain\Apartment\VapiAssistantConfig;
use App\Infrastructure\Vapi\VapiAssistantService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class VapiAssistantServiceTest extends TestCase
{
    public function testSyncAssistantThrowsWhenApiKeyIsEmpty(): void
    {
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock->expects($this->never())->method('request');

        $loggerMock = $this->createMock(LoggerInterface::class);

        $loggerMock->expects($this->once())
            ->method('warning')
            ->with('VAPI_API_KEY is not configured or is a placeholder — skipping Assistant sync.');

        $service = new VapiAssistantService(
            $httpClientMock,
            $loggerMock,
            '', // Empty API key
            'https://api.vapi.ai'
        );

        $config = new VapiAssistantConfig('prompt', 'first message', 60);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('VAPI_API_KEY is not configured.');

        $service->syncAssistant($config);
    }

    public function testSyncAssistantThrowsWhenApiKeyIsPlaceholder(): void
    {
        $httpClientMock = $this->createMock(HttpClientInterface::class);
        $httpClientMock->expects($this->never())->method('request');

        $loggerMock = $this->createMock(LoggerInterface::class);

        $loggerMock->expects($this->once())
            ->method('warning')
            ->with('VAPI_API_KEY is not configured or is a placeholder — skipping Assistant sync.');

        $service = new VapiAssistantService(
            $httpClientMock,
            $loggerMock,
            'some_prefix_your_vapi_api_key_here_some_suffix', // Placeholder API key
            'https://api.vapi.ai'
        );

        $config = new VapiAssistantConfig('prompt', 'first message', 60);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('VAPI_API_KEY is not configured.');

        $service->syncAssistant($config);
    }
}
