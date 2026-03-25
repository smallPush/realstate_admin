<?php

namespace App\Tests\Controller;

use App\Application\Apartment\Command\UpdateVapiAssistantConfigCommand;
use App\Application\Apartment\Command\UpdateVapiAssistantConfigCommandHandler;
use App\Application\Apartment\Query\GetVapiAssistantConfigQuery;
use App\Controller\VapiAssistantController;
use App\Domain\Apartment\VapiAssistantConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class VapiAssistantControllerTest extends TestCase
{
    private GetVapiAssistantConfigQuery $queryMock;
    private UpdateVapiAssistantConfigCommandHandler $handlerMock;
    private VapiAssistantController $controller;

    protected function setUp(): void
    {
        $this->queryMock = $this->createMock(GetVapiAssistantConfigQuery::class);
        $this->handlerMock = $this->createMock(UpdateVapiAssistantConfigCommandHandler::class);
        $this->controller = new VapiAssistantController();
    }

    public function testGetConfigReturnsNotFoundWhenNull(): void
    {
        $this->queryMock->expects($this->once())
            ->method('execute')
            ->willReturn(null);

        $response = $this->controller->getConfig($this->queryMock);

        $this->assertSame(404, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('error', $content['status']);
        $this->assertSame('Configuration not found.', $content['message']);
    }

    public function testGetConfigReturnsData(): void
    {
        $date = new \DateTimeImmutable('2024-03-13T10:00:00+00:00');
        $config = new VapiAssistantConfig('Test prompt', 'Hello', 120, $date);

        $this->queryMock->expects($this->once())
            ->method('execute')
            ->willReturn($config);

        $response = $this->controller->getConfig($this->queryMock);

        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('success', $content['status']);
        $this->assertSame('Test prompt', $content['data']['prompt']);
        $this->assertSame('Hello', $content['data']['firstMessage']);
        $this->assertSame(120, $content['data']['timeLimit']);
        $this->assertSame($date->format(\DateTimeInterface::ATOM), $content['data']['updatedAt']);
    }

    public function testUpdateConfigReturnsBadRequestForInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->updateConfig($request, $this->handlerMock);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Invalid JSON payload', $response->getContent());
    }

    public function testUpdateConfigReturnsBadRequestForMissingFields(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['prompt' => 'Test']));

        $response = $this->controller->updateConfig($request, $this->handlerMock);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Missing required fields', $response->getContent());
    }

    public function testUpdateConfigReturnsBadRequestForInvalidTimeLimitType(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'prompt' => 'Test',
            'firstMessage' => 'Hello',
            'timeLimit' => '120' // string instead of int
        ]));

        $response = $this->controller->updateConfig($request, $this->handlerMock);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('timeLimit must be an integer', $response->getContent());
    }

    public function testUpdateConfigReturnsSuccess(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'prompt' => 'Test',
            'firstMessage' => 'Hello',
            'timeLimit' => 120
        ]));

        $this->handlerMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function (UpdateVapiAssistantConfigCommand $command) {
                return $command->prompt === 'Test' &&
                       $command->firstMessage === 'Hello' &&
                       $command->timeLimit === 120;
            }));

        $response = $this->controller->updateConfig($request, $this->handlerMock);

        $this->assertSame(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertSame('success', $content['status']);
        $this->assertSame('Assistant configuration updated successfully.', $content['message']);
    }

    public function testUpdateConfigReturnsServerErrorOnException(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'prompt' => 'Test',
            'firstMessage' => 'Hello',
            'timeLimit' => 120
        ]));

        $this->handlerMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('API error'));

        $response = $this->controller->updateConfig($request, $this->handlerMock);

        $this->assertSame(500, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Failed to sync with Vapi: API error', $content['error']);
    }
}
