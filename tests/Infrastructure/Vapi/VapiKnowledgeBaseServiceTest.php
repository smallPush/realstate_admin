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
            '/tmp'
        );

        $service->syncKnowledgeBase();
    }
}
