<?php

namespace App\Tests\Application\Apartment;

use App\Application\Apartment\Command\UpdateVapiAssistantConfigCommand;
use PHPUnit\Framework\TestCase;

class UpdateVapiAssistantConfigCommandTest extends TestCase
{
    public function testConstructorAssignsProperties(): void
    {
        $prompt = 'Test prompt';
        $firstMessage = 'Test first message';
        $timeLimit = 300;

        $command = new UpdateVapiAssistantConfigCommand($prompt, $firstMessage, $timeLimit);

        $this->assertSame($prompt, $command->prompt);
        $this->assertSame($firstMessage, $command->firstMessage);
        $this->assertSame($timeLimit, $command->timeLimit);
    }
}
