<?php

namespace App\Controller;

use App\Application\Apartment\Command\UpdateVapiAssistantConfigCommand;
use App\Application\Apartment\Command\UpdateVapiAssistantConfigCommandHandler;
use App\Application\Apartment\Query\GetVapiAssistantConfigQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/vapi/assistant', name: 'api_vapi_assistant_')]
class VapiAssistantController extends AbstractController
{
    #[Route('/config', name: 'get_config', methods: ['GET'])]
    public function getConfig(GetVapiAssistantConfigQuery $query): JsonResponse
    {
        $config = $query->execute();

        if (!$config) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Configuration not found.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => [
                'prompt' => $config->getPrompt(),
                'firstMessage' => $config->getFirstMessage(),
                'timeLimit' => $config->getTimeLimit(),
                'updatedAt' => $config->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ],
        ]);
    }

    #[Route('/config', name: 'update_config', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateConfig(Request $request, UpdateVapiAssistantConfigCommandHandler $handler): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON payload'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $prompt = $data['prompt'] ?? null;
        $firstMessage = $data['firstMessage'] ?? null;
        $timeLimit = $data['timeLimit'] ?? null;

        if ($prompt === null || $firstMessage === null || $timeLimit === null) {
            return new JsonResponse(['error' => 'Missing required fields: prompt, firstMessage, timeLimit'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!is_int($timeLimit)) {
            return new JsonResponse(['error' => 'timeLimit must be an integer'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $command = new UpdateVapiAssistantConfigCommand((string)$prompt, (string)$firstMessage, $timeLimit);

        try {
            $handler->execute($command);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to sync with Vapi: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Assistant configuration updated successfully.',
        ]);
    }
}
