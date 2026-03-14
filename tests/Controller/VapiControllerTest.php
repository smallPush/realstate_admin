<?php

namespace App\Tests\Controller;

use App\Application\Apartment\Query\GetAvailableApartmentsQuery;
use App\Controller\VapiController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class VapiControllerTest extends TestCase
{
    public function testVapiWebhookReturnsUnauthorizedOnMissingSecret(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        $request = new Request();
        // Secret is configured as 'test_secret' but not provided in headers

        $response = $controller->vapiWebhook($request, $queryMock, 'test_secret');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $content['error']);
    }

    public function testVapiWebhookReturnsUnauthorizedOnInvalidSecret(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        $request = new Request();
        $request->headers->set('x-vapi-secret', 'wrong_secret');

        $response = $controller->vapiWebhook($request, $queryMock, 'test_secret');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testVapiWebhookReturnsBadRequestOnInvalidJson(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        // Create request with invalid JSON and valid secret
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['HTTP_x-vapi-secret' => 'test_secret'],
            '{"invalid_json": "missing_quote}'
        );

        $response = $controller->vapiWebhook($request, $queryMock, 'test_secret');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Invalid JSON payload', $content['error']);
    }
}
