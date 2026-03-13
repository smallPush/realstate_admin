<?php

namespace App\Domain\Apartment;

interface VapiAssistantConfigRepositoryInterface
{
    public function getConfig(): ?VapiAssistantConfig;
    public function save(VapiAssistantConfig $config): void;
}
