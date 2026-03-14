<?php

namespace App\Tests\Application\Apartment;

use App\Application\Apartment\Command\SyncKnowledgeBaseCommand;
use App\Domain\Apartment\VapiKnowledgeBaseServiceInterface;
use PHPUnit\Framework\TestCase;

class SyncKnowledgeBaseCommandTest extends TestCase
{
    public function testExecuteCallsSyncKnowledgeBase(): void
    {
        $vapiKnowledgeBaseServiceMock = $this->createMock(VapiKnowledgeBaseServiceInterface::class);

        $vapiKnowledgeBaseServiceMock->expects($this->once())
            ->method('syncKnowledgeBase');

        $command = new SyncKnowledgeBaseCommand($vapiKnowledgeBaseServiceMock);
        $command->execute();
    }
}
