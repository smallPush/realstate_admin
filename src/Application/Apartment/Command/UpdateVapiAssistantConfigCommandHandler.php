<?php

namespace App\Application\Apartment\Command;

use App\Domain\Apartment\VapiAssistantConfig;
use App\Domain\Apartment\VapiAssistantConfigRepositoryInterface;
use App\Domain\Apartment\VapiAssistantServiceInterface;
use Psr\Log\LoggerInterface;

class UpdateVapiAssistantConfigCommandHandler
{
    public function __construct(
        private readonly VapiAssistantConfigRepositoryInterface $repository,
        private readonly VapiAssistantServiceInterface $vapiAssistantService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(UpdateVapiAssistantConfigCommand $command): void
    {
        $config = $this->repository->getConfig();

        if ($config === null) {
            $config = new VapiAssistantConfig(
                $command->prompt,
                $command->firstMessage,
                $command->timeLimit
            );
        } else {
            $config->update(
                $command->prompt,
                $command->firstMessage,
                $command->timeLimit
            );
        }

        $this->repository->save($config);

        try {
            $this->vapiAssistantService->syncAssistant($config);
            $this->logger->info('Successfully synced updated Assistant config with Vapi.');
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync Assistant config with Vapi: ' . $e->getMessage());
            throw $e;
        }
    }
}
