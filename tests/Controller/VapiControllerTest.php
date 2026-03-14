<?php

namespace App\Tests\Controller;

use App\Application\Apartment\Query\GetAvailableApartmentsQuery;
use App\Controller\VapiController;
use App\Domain\Apartment\Apartment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class VapiControllerTest extends TestCase
{
    public function testGetAvailableApartmentsReturnsSuccess(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        $apartment1 = new Apartment('Apartment 1', 'Address 1', 1000, true, 1);
        $apartment2 = new Apartment('Apartment 2', 'Address 2', 1500, true, 2);

        $queryMock->expects($this->once())
            ->method('execute')
            ->willReturn([$apartment1, $apartment2]);

        $response = $controller->getAvailableApartments($queryMock);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertIsArray($content);
        $this->assertArrayHasKey('status', $content);
        $this->assertEquals('success', $content['status']);

        $this->assertArrayHasKey('message', $content);
        $this->assertEquals('Estos son los pisos disponibles.', $content['message']);

        $this->assertArrayHasKey('data', $content);
        $this->assertCount(2, $content['data']);

        $this->assertEquals(1, $content['data'][0]['id']);
        $this->assertEquals('Apartment 1', $content['data'][0]['name']);
        $this->assertEquals('Address 1', $content['data'][0]['address']);
        $this->assertEquals(1000, $content['data'][0]['price']);

        $this->assertEquals(2, $content['data'][1]['id']);
        $this->assertEquals('Apartment 2', $content['data'][1]['name']);
        $this->assertEquals('Address 2', $content['data'][1]['address']);
        $this->assertEquals(1500, $content['data'][1]['price']);
    }

    public function testGetAvailableApartmentsReturnsEmptyList(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        $queryMock->expects($this->once())
            ->method('execute')
            ->willReturn([]);

        $response = $controller->getAvailableApartments($queryMock);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        $this->assertIsArray($content);
        $this->assertArrayHasKey('status', $content);
        $this->assertEquals('success', $content['status']);

        $this->assertArrayHasKey('data', $content);
        $this->assertIsArray($content['data']);
        $this->assertCount(0, $content['data']);
    }

    public function testVapiWebhookReturnsBadRequestOnInvalidJson(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        // Create request with invalid JSON
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            '{"invalid_json": "missing_quote}'
        );

        $response = $controller->vapiWebhook($request, $queryMock);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Invalid JSON payload', $content['error']);
    }
}
