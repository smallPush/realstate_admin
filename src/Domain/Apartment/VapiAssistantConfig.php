<?php

namespace App\Domain\Apartment;

class VapiAssistantConfig
{
    private ?string $id = null;
    private string $prompt;
    private string $firstMessage;
    private int $timeLimit;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $prompt,
        string $firstMessage,
        int $timeLimit,
        ?\DateTimeImmutable $updatedAt = null
    ) {
        $this->prompt = $prompt;
        $this->firstMessage = $firstMessage;
        $this->timeLimit = $timeLimit;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getFirstMessage(): string
    {
        return $this->firstMessage;
    }

    public function getTimeLimit(): int
    {
        return $this->timeLimit;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(string $prompt, string $firstMessage, int $timeLimit): void
    {
        $this->prompt = $prompt;
        $this->firstMessage = $firstMessage;
        $this->timeLimit = $timeLimit;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
