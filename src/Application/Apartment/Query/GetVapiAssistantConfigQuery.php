<?php

namespace App\Application\Apartment\Query;

use App\Domain\Apartment\VapiAssistantConfig;
use App\Domain\Apartment\VapiAssistantConfigRepositoryInterface;

class GetVapiAssistantConfigQuery
{
    public function __construct(
        private readonly VapiAssistantConfigRepositoryInterface $repository
    ) {
    }

    public function execute(): ?VapiAssistantConfig
    {
        return $this->repository->getConfig();
    }
}
