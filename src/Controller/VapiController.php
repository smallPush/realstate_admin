<?php

namespace App\Controller;

use App\Application\Apartment\Query\GetAvailableApartmentsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class VapiController extends AbstractController
{
    #[Route('/api/vapi/apartments', name: 'api_vapi_apartments', methods: ['GET'])]
    public function getAvailableApartments(
        Request $request,
        GetAvailableApartmentsQuery $getAvailableApartmentsQuery,
        #[Autowire(env: 'VAPI_WEBHOOK_SECRET')] string $webhookSecret = ''
    ): JsonResponse {
        if ($webhookSecret === '') {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $providedSecret = $request->headers->get('x-vapi-secret', '');
        if (!hash_equals($webhookSecret, $providedSecret)) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $apartments = $getAvailableApartmentsQuery->execute();

        $data = array_map(static fn($apartment) => [
            'id' => $apartment->getId(),
            'name' => $apartment->getName(),
            'address' => $apartment->getAddress(),
            'price' => $apartment->getPrice(),
        ], $apartments);

        // Devolver la información en formato JSON para que Vapi (o la IA) la consuma
        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
            'message' => 'Estos son los pisos disponibles.',
        ]);
    }
    
    #[Route('/api/vapi/webhook', name: 'api_vapi_webhook', methods: ['POST'])]
    public function vapiWebhook(
        Request $request,
        GetAvailableApartmentsQuery $getAvailableApartmentsQuery,
        #[Autowire(env: 'VAPI_WEBHOOK_SECRET')] string $webhookSecret = ''
    ): JsonResponse {
        if ($webhookSecret === '') {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $providedSecret = $request->headers->get('x-vapi-secret', '');
        if (!hash_equals($webhookSecret, $providedSecret)) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $content = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON payload'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!isset($content['message']) || !is_array($content['message'])) {
            return new JsonResponse(['error' => 'Invalid webhook format'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $message = $content['message'];
        $toolCalls = match ($message['type'] ?? null) {
            'tool-calls' => $message['toolCallList'] ?? [],
            'function-call' => isset($message['functionCall']) ? [$message['functionCall']] : [],
            default => [],
        };

        foreach ($toolCalls as $toolCall) {
            if (($toolCall['name'] ?? null) === 'getAvailableApartments') {
                $apartments = $getAvailableApartmentsQuery->execute();

                $data = array_map(static fn($apartment) => [
                    'name' => $apartment->getName(),
                    'address' => $apartment->getAddress(),
                    'price' => $apartment->getPrice(),
                ], $apartments);
                
                return new JsonResponse([
                    'results' => [
                        [
                            'toolCallId' => $toolCall['id'] ?? null,
                            'result' => json_encode([
                                'message' => 'Estos son los pisos disponibles.',
                                'apartments' => $data
                            ])
                        ]
                    ]
                ]);
            }
        }

        // Si no es un tool call, devolver la base de conocimiento o mensaje genérico
        return new JsonResponse([
            'message' => 'Webhook received successfully'
        ]);
    }
}
