<?php

namespace App\Controller;

use App\Repository\ApartmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class VapiController extends AbstractController
{
    #[Route('/api/vapi/apartments', name: 'api_vapi_apartments', methods: ['GET'])]
    public function getAvailableApartments(ApartmentRepository $apartmentRepository): JsonResponse
    {
        // Obtener solo los pisos que están disponibles
        $apartments = $apartmentRepository->findBy(['isAvailable' => true]);

        $data = [];
        foreach ($apartments as $apartment) {
            $data[] = [
                'id' => $apartment->getId(),
                'name' => $apartment->getName(),
                'address' => $apartment->getAddress(),
                'price' => $apartment->getPrice(),
            ];
        }

        // Devolver la información en formato JSON para que Vapi (o la IA) la consuma
        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
            'message' => 'Estos son los pisos disponibles.',
        ]);
    }
    
    #[Route('/api/vapi/webhook', name: 'api_vapi_webhook', methods: ['POST'])]
    public function vapiWebhook(Request $request, ApartmentRepository $apartmentRepository): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        
        // Manejar el webhook de Vapi para funciones, por ejemplo `getAvailableApartments`
        if (isset($content['message']['type']) && $content['message']['type'] === 'function-call') {
            $functionCall = $content['message']['functionCall'];
            
            if ($functionCall['name'] === 'getAvailableApartments') {
                $apartments = $apartmentRepository->findBy(['isAvailable' => true]);
                
                $data = [];
                foreach ($apartments as $apartment) {
                    $data[] = [
                        'name' => $apartment->getName(),
                        'address' => $apartment->getAddress(),
                        'price' => $apartment->getPrice(),
                    ];
                }
                
                return new JsonResponse([
                    'results' => [
                        [
                            'toolCallId' => $functionCall['id'],
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
