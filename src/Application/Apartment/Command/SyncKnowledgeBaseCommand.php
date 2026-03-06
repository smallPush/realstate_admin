<?php

namespace App\Application\Apartment\Command;

use App\Domain\Apartment\VapiKnowledgeBaseServiceInterface;

class SyncKnowledgeBaseCommand
{
    private VapiKnowledgeBaseServiceInterface $vapiKnowledgeBaseService;

    public function __construct(VapiKnowledgeBaseServiceInterface $vapiKnowledgeBaseService)
    {
        $this->vapiKnowledgeBaseService = $vapiKnowledgeBaseService;
    }

    public function execute(): void
    {
        $this->vapiKnowledgeBaseService->syncKnowledgeBase();
    }
}
