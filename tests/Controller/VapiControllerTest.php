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
    public function testGetAvailableApartmentsReturnsUnauthorizedOnEmptyConfiguredSecret(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        $request = new Request();
        // Even if headers contain a secret, if configured secret is empty it should fail
        $request->headers->set('x-vapi-secret', 'any_secret');

        $response = $controller->getAvailableApartments($request, $queryMock, '');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $content['error']);
    }

    public function testGetAvailableApartmentsReturnsUnauthorizedOnMissingSecret(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        $request = new Request();
        // Secret is configured as 'test_secret' but not provided in headers

        $response = $controller->getAvailableApartments($request, $queryMock, 'test_secret');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $content['error']);
    }

    public function testGetAvailableApartmentsReturnsUnauthorizedOnInvalidSecret(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        $request = new Request();
        $request->headers->set('x-vapi-secret', 'wrong_secret');

        $response = $controller->getAvailableApartments($request, $queryMock, 'test_secret');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testVapiWebhookReturnsUnauthorizedOnEmptyConfiguredSecret(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);

        $request = new Request();
        // Even if headers contain a secret, if configured secret is empty it should fail
        $request->headers->set('x-vapi-secret', 'any_secret');

        $response = $controller->vapiWebhook($request, $queryMock, '');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(JsonResponse::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $content['error']);
    }

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

    public function testVapiWebhookHandlesCurrentToolCallsFormat(): void
    {
        $controller = new VapiController();
        $queryMock = $this->createMock(GetAvailableApartmentsQuery::class);
        $queryMock->expects($this->once())
            ->method('execute')
            ->willReturn([
                new Apartment('Piso Centro', 'Calle Mayor 1', 1200, true, 1),
            ]);

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['HTTP_X_VAPI_SECRET' => 'test_secret'],
            json_encode([
                'message' => [
                    'type' => 'tool-calls',
                    'toolCallList' => [[
                        'id' => 'tool-call-1',
                        'name' => 'getAvailableApartments',
                        'arguments' => [],
                    ]],
                ],
            ], JSON_THROW_ON_ERROR)
        );

        $response = $controller->vapiWebhook($request, $queryMock, 'test_secret');

        $this->assertSame(JsonResponse::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('tool-call-1', $content['results'][0]['toolCallId']);
        $result = json_decode($content['results'][0]['result'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Piso Centro', $result['apartments'][0]['name']);
    }
}
