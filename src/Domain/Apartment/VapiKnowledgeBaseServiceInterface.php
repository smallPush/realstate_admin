<?php

namespace App\Domain\Apartment;

interface VapiKnowledgeBaseServiceInterface
{
    /**
     * Sync all apartments to Vapi as a Knowledge Base document.
     */
    public function syncKnowledgeBase(): void;
}
