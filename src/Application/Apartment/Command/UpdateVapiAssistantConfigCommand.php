<?php

namespace App\Application\Apartment\Command;

class UpdateVapiAssistantConfigCommand
{
    public function __construct(
        public readonly string $prompt,
        public readonly string $firstMessage,
        public readonly int $timeLimit
    ) {
    }
}
