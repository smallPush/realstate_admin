<?php

namespace App\Tests\Domain\Apartment;

use App\Domain\Apartment\VapiAssistantConfig;
use PHPUnit\Framework\TestCase;

final class VapiAssistantConfigTest extends TestCase
{
    public function testConstructorAssignsPropertiesWithDefaults(): void
    {
        $config = new VapiAssistantConfig('You are a helpful assistant.', 'Hello!', 60);

        $this->assertNull($config->getId());
        $this->assertSame('You are a helpful assistant.', $config->getPrompt());
        $this->assertSame('Hello!', $config->getFirstMessage());
        $this->assertSame(60, $config->getTimeLimit());
        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getUpdatedAt());
    }

    public function testConstructorAssignsAllProperties(): void
    {
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');
        $config = new VapiAssistantConfig('System prompt', 'Hi there', 120, $date);

        $this->assertSame('System prompt', $config->getPrompt());
        $this->assertSame('Hi there', $config->getFirstMessage());
        $this->assertSame(120, $config->getTimeLimit());
        $this->assertSame($date, $config->getUpdatedAt());
    }

    public function testUpdateMethodChangesPropertiesAndUpdatedAt(): void
    {
        $date = new \DateTimeImmutable('2023-01-01 12:00:00');
        $config = new VapiAssistantConfig('Old prompt', 'Old message', 60, $date);

        $config->update('New prompt', 'New message', 90);

        $this->assertSame('New prompt', $config->getPrompt());
        $this->assertSame('New message', $config->getFirstMessage());
        $this->assertSame(90, $config->getTimeLimit());

        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getUpdatedAt());
        $this->assertNotSame($date, $config->getUpdatedAt());
        $this->assertGreaterThan($date, $config->getUpdatedAt());
    }
}
