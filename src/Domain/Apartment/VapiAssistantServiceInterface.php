<?php

namespace App\Domain\Apartment;

interface VapiAssistantServiceInterface
{
    /**
     * Creates or updates the Vapi Assistant and returns the new or updated Assistant ID.
     */
    public function syncAssistant(VapiAssistantConfig $config): string;
}
