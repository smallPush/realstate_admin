<?php

namespace App\Tests\Controller;

use App\Application\Apartment\Query\GetAvailableApartmentsQuery;
use App\Controller\VapiController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class VapiControllerTest extends TestCase
{
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
