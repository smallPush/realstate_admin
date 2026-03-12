<?php

namespace App\Tests\Controller;

use App\Application\Apartment\Query\GetAvailableApartmentsQuery;
use App\Controller\VapiController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class VapiControllerTest extends TestCase
{
    public function testVapiWebhookInvalidWebhookFormatMissingMessage(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        $request = new Request([], [], [], [], [], [], json_encode(['not_message' => 'value']));

        $response = $controller->vapiWebhook($request, $queryMock);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid webhook format', $responseData['error']);
    }

    public function testVapiWebhookInvalidWebhookFormatMessageNotArray(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        $request = new Request([], [], [], [], [], [], json_encode(['message' => 'not_an_array']));

        $response = $controller->vapiWebhook($request, $queryMock);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Invalid webhook format', $responseData['error']);
    }
}
