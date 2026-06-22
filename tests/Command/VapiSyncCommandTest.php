<?php

namespace App\Tests\Command;

use App\Application\Apartment\Command\SyncKnowledgeBaseCommand;
use App\Command\VapiSyncCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class VapiSyncCommandTest extends TestCase
{
    private SyncKnowledgeBaseCommand $syncKnowledgeBaseCommand;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->syncKnowledgeBaseCommand = $this->createMock(SyncKnowledgeBaseCommand::class);

        $application = new Application();
        $application->add(new VapiSyncCommand($this->syncKnowledgeBaseCommand));

        $command = $application->find('app:vapi-sync');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteSuccess(): void
    {
        $this->syncKnowledgeBaseCommand->expects($this->once())
            ->method('execute');

        $this->commandTester->execute([]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Starting synchronization process...', $output);
        $this->assertStringContainsString('Synchronization completed successfully!', $output);
    }

    public function testExecuteFailure(): void
    {
        $this->syncKnowledgeBaseCommand->expects($this->once())
            ->method('execute')
            ->willThrowException(new \RuntimeException('Network error'));

        $this->commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Synchronization failed: Network error', $output);
    }
}
