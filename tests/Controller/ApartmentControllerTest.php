<?php

namespace App\Tests\Controller;

use App\Application\Apartment\Command\SyncKnowledgeBaseCommand;
use App\Application\Apartment\Command\UpdateApartmentCommand;
use App\Application\Apartment\Query\GetAllApartmentsQuery;
use App\Controller\ApartmentController;
use App\Domain\Apartment\Apartment as DomainApartment;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ApartmentControllerTest extends TestCase
{
    private ApartmentController $controller;
    private UpdateApartmentCommand $updateCommandMock;
    private SyncKnowledgeBaseCommand $syncCommandMock;
    private ContainerInterface $containerMock;
    private Environment $twigMock;

    protected function setUp(): void
    {
        $this->updateCommandMock = $this->createMock(UpdateApartmentCommand::class);
        $this->syncCommandMock = $this->createMock(SyncKnowledgeBaseCommand::class);

        $entityManagerMock = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $this->controller = new ApartmentController(
            $this->updateCommandMock,
            $this->syncCommandMock,
            $entityManagerMock
        );

        $this->twigMock = $this->createMock(Environment::class);

        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->containerMock->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['twig', true]
            ]);

        $this->controller->setContainer($this->containerMock);
    }

    public function testPublicListReturnsResponseWithRenderedTwigTemplate(): void
    {
        $this->containerMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['twig', $this->twigMock]
            ]);

        $dummyApartment1 = $this->createMock(DomainApartment::class);
        $dummyApartment2 = $this->createMock(DomainApartment::class);

        $apartments = [$dummyApartment1, $dummyApartment2];

        $this->twigMock->expects($this->once())
            ->method('render')
            ->with('apartment/public_list.html.twig', ['apartments' => $apartments])
            ->willReturn('<html>Rendered public list content</html>');

        $getAllApartmentsQueryMock = $this->createMock(GetAllApartmentsQuery::class);
        $getAllApartmentsQueryMock->expects($this->once())
            ->method('execute')
            ->willReturn($apartments);

        $response = $this->controller->publicList($getAllApartmentsQueryMock);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('<html>Rendered public list content</html>', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSyncVapiErrorAddsFlashAndRedirects(): void
    {
        $this->syncCommandMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Vapi API error'));

        $routerMock = $this->createMock(\Symfony\Component\Routing\RouterInterface::class);
        $routerMock->expects($this->once())
            ->method('generate')
            ->with('apartment_admin_index')
            ->willReturn('/admin/apartments');

        $flashBagMock = $this->createMock(\Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface::class);
        $flashBagMock->expects($this->once())
            ->method('add')
            ->with('error', 'Error al sincronizar con Vapi: Vapi API error');

        $sessionMock = $this->createMock(\Symfony\Component\HttpFoundation\Session\Session::class);
        $sessionMock->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBagMock);

        $requestStackMock = $this->createMock(\Symfony\Component\HttpFoundation\RequestStack::class);
        $requestStackMock->expects($this->once())
            ->method('getSession')
            ->willReturn($sessionMock);

        $tokenManagerMock = $this->createMock(\Symfony\Component\Security\Csrf\CsrfTokenManagerInterface::class);
        $tokenManagerMock->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(true);

        // Replace global setUp expectation for has() to avoid Conflicts
        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->controller->setContainer($this->containerMock);

        $this->containerMock->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['router', true],
                ['request_stack', true],
                ['twig', true],
                ['security.csrf.token_manager', true],
            ]);

        $this->containerMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['router', $routerMock],
                ['request_stack', $requestStackMock],
                ['twig', $this->twigMock],
                ['security.csrf.token_manager', $tokenManagerMock],
            ]);

        $request = new \Symfony\Component\HttpFoundation\Request([], ['_token' => 'valid-token']);

        $response = $this->controller->syncVapi($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/admin/apartments', $response->getTargetUrl());
    }

    public function testSyncVapiThrowsAccessDeniedExceptionOnInvalidCsrfToken(): void
    {
        $tokenManagerMock = $this->createMock(\Symfony\Component\Security\Csrf\CsrfTokenManagerInterface::class);
        $tokenManagerMock->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false);

        // Replace global setUp expectation for has() to avoid Conflicts
        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->controller->setContainer($this->containerMock);

        $this->containerMock->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['security.csrf.token_manager', true],
            ]);

        $this->containerMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['security.csrf.token_manager', $tokenManagerMock],
            ]);

        $request = new \Symfony\Component\HttpFoundation\Request([], ['_token' => 'invalid-token']);

        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $this->controller->syncVapi($request);
    }
}
