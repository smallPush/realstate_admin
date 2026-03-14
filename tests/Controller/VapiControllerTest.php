<?php

namespace App\Tests\Controller;

use App\Application\Apartment\Query\GetAvailableApartmentsQuery;
use App\Controller\VapiController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class VapiControllerTest extends TestCase
{
    public function testVapiWebhookReturnsUnauthorizedOnMissingOrInvalidSecret(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);
        $secret = 'test_secret';

        // 1. Missing secret
        $request = new Request();
        $response = $controller->vapiWebhook($request, $queryMock, $secret);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());

        // 2. Invalid secret
        $request = new Request();
        $request->headers->set('x-vapi-secret', 'wrong_secret');
        $response = $controller->vapiWebhook($request, $queryMock, $secret);

        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testVapiWebhookReturnsBadRequestOnInvalidJson(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);
        $secret = 'test_secret';

        // Create request with invalid JSON
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['HTTP_X_VAPI_SECRET' => $secret],
            '{"invalid_json": "missing_quote}'
        );

        $response = $controller->vapiWebhook($request, $queryMock, $secret);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Invalid JSON payload', $content['error']);
    }
}
