<?php

namespace App\Tests\Application\Apartment;

use App\Application\Apartment\Command\UpdateVapiAssistantConfigCommand;
use App\Application\Apartment\Command\UpdateVapiAssistantConfigCommandHandler;
use App\Domain\Apartment\VapiAssistantConfig;
use App\Domain\Apartment\VapiAssistantConfigRepositoryInterface;
use App\Domain\Apartment\VapiAssistantServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateVapiAssistantConfigCommandHandlerTest extends TestCase
{
    private VapiAssistantConfigRepositoryInterface $repository;
    private VapiAssistantServiceInterface $vapiService;
    private LoggerInterface $logger;
    private UpdateVapiAssistantConfigCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VapiAssistantConfigRepositoryInterface::class);
        $this->vapiService = $this->createMock(VapiAssistantServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new UpdateVapiAssistantConfigCommandHandler(
            $this->repository,
            $this->vapiService,
            $this->logger
        );
    }

    public function testExecuteCreatesNewConfigIfNoneExists(): void
    {
        $command = new UpdateVapiAssistantConfigCommand('New prompt', 'New first message', 120);

        $this->repository->expects($this->once())
            ->method('getConfig')
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (VapiAssistantConfig $config) {
                return $config->getPrompt() === 'New prompt' &&
                       $config->getFirstMessage() === 'New first message' &&
                       $config->getTimeLimit() === 120;
            }));

        $this->vapiService->expects($this->once())
            ->method('syncAssistant');

        $this->handler->execute($command);
    }

    public function testExecuteUpdatesExistingConfig(): void
    {
        $command = new UpdateVapiAssistantConfigCommand('Updated prompt', 'Updated message', 60);
        $existingConfig = new VapiAssistantConfig('Old prompt', 'Old message', 30);

        $this->repository->expects($this->once())
            ->method('getConfig')
            ->willReturn($existingConfig);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($existingConfig);

        $this->vapiService->expects($this->once())
            ->method('syncAssistant')
            ->with($existingConfig);

        $this->handler->execute($command);

        $this->assertSame('Updated prompt', $existingConfig->getPrompt());
        $this->assertSame('Updated message', $existingConfig->getFirstMessage());
        $this->assertSame(60, $existingConfig->getTimeLimit());
    }

    public function testExecuteLogsAndThrowsIfSyncFails(): void
    {
        $command = new UpdateVapiAssistantConfigCommand('Prompt', 'Message', 60);

        $this->repository->expects($this->once())
            ->method('getConfig')
            ->willReturn(null);

        $this->vapiService->expects($this->once())
            ->method('syncAssistant')
            ->willThrowException(new \RuntimeException('Sync failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to sync Vapi Assistant config to API: Sync failed');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Sync failed');

        $this->handler->execute($command);
    }
}
