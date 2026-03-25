<?php

namespace App\Infrastructure\Vapi;

use App\Domain\Apartment\VapiAssistantConfig;
use App\Domain\Apartment\VapiAssistantServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class VapiAssistantService implements VapiAssistantServiceInterface
{
    private string $apiKey;
    private ?string $assistantId;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        string $vapiApiKey,
        ?string $vapiAssistantId = null
    ) {
        $this->apiKey = $vapiApiKey;
        $this->assistantId = $vapiAssistantId;
    }

    public function syncAssistant(VapiAssistantConfig $config): string
    {
        if (empty($this->apiKey)) {
            $this->logger->warning('VAPI_API_KEY is not set — skipping Assistant sync.');
            throw new \RuntimeException('VAPI_API_KEY is not set.');
        }

        $payload = [
            'model' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $config->getPrompt(),
                    ]
                ]
            ],
            'firstMessage' => $config->getFirstMessage(),
            'maxDurationSeconds' => $config->getTimeLimit(),
        ];

        // Ensure we only pass necessary headers
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ];

        try {
            if (!empty($this->assistantId)) {
                // Update existing assistant
                $response = $this->httpClient->request('PATCH', 'https://api.vapi.ai/assistant/' . $this->assistantId, $options);
                $this->logger->info('Vapi: updated assistant {id}', ['id' => $this->assistantId]);
                $data = $response->toArray();
                return $data['id'] ?? $this->assistantId;
            } else {
                // Create new assistant
                $response = $this->httpClient->request('POST', 'https://api.vapi.ai/assistant', $options);
                $data = $response->toArray();

                if (!isset($data['id'])) {
                    $this->logger->error('Vapi: create assistant response did not contain id', ['response' => $data]);
                    throw new \RuntimeException('Failed to create Vapi Assistant. ID not found in response.');
                }

                $this->assistantId = $data['id'];
                $this->logger->info('Vapi: created new assistant with id {id}', ['id' => $this->assistantId]);
                return $this->assistantId;
            }
        } catch (\Throwable $e) {
            $this->logger->error('Vapi: failed to sync assistant: {error}', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Failed to sync Vapi Assistant: ' . $e->getMessage());
        }
    }
}
