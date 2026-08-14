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

    public function testEditThrowsAccessDeniedExceptionWhenUserIsNull(): void
    {
        $tokenMock = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $tokenMock->expects($this->any())
            ->method('getUser')
            ->willReturn(null);

        $tokenStorageMock = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class);
        $tokenStorageMock->expects($this->any())
            ->method('getToken')
            ->willReturn($tokenMock);

        $authCheckerMock = $this->createMock(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class);
        $authCheckerMock->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->controller->setContainer($this->containerMock);

        $this->containerMock->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['security.authorization_checker', true],
                ['security.token_storage', true],
            ]);

        $this->containerMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['security.authorization_checker', $authCheckerMock],
                ['security.token_storage', $tokenStorageMock],
            ]);

        $request = new \Symfony\Component\HttpFoundation\Request();

        $apartmentMock = $this->createMock(\App\Infrastructure\Persistence\Doctrine\Entity\Apartment::class);

        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);

        $this->controller->edit($request, $apartmentMock);
    }

    public function testEditRemovesApartmentGroupsFieldForNonAdmin(): void
    {
        $userMock = $this->createMock(\App\Infrastructure\Persistence\Doctrine\Entity\User::class);
        $userMock->expects($this->once())
            ->method('getApartmentGroups')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection());

        $tokenMock = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $tokenMock->expects($this->any())
            ->method('getUser')
            ->willReturn($userMock);

        $tokenStorageMock = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class);
        $tokenStorageMock->expects($this->any())
            ->method('getToken')
            ->willReturn($tokenMock);

        $authCheckerMock = $this->createMock(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class);
        // We will call isGranted 3 times in edit:
        // 1. First authorization check
        // 2. Form remove field check
        $authCheckerMock->expects($this->exactly(2))
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $formFactoryMock = $this->createMock(\Symfony\Component\Form\FormFactoryInterface::class);
        $formMock = $this->createMock(\Symfony\Component\Form\FormInterface::class);

        $formMock->expects($this->once())
            ->method('remove')
            ->with('apartmentGroups');

        $formFactoryMock->expects($this->once())
            ->method('create')
            ->with(\App\Form\ApartmentType::class)
            ->willReturn($formMock);

        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->controller->setContainer($this->containerMock);

        $this->containerMock->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['security.authorization_checker', true],
                ['security.token_storage', true],
                ['form.factory', true],
                ['twig', true],
            ]);

        $this->containerMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['security.authorization_checker', $authCheckerMock],
                ['security.token_storage', $tokenStorageMock],
                ['form.factory', $formFactoryMock],
                ['twig', $this->twigMock],
            ]);

        $request = new \Symfony\Component\HttpFoundation\Request();

        // Make apartment return groups to pass the first check
        $groupMock = $this->createMock(\App\Infrastructure\Persistence\Doctrine\Entity\ApartmentGroup::class);
        $groupMock->expects($this->any())->method('getId')->willReturn(1);
        $userMock = $this->createMock(\App\Infrastructure\Persistence\Doctrine\Entity\User::class);
        $userMock->expects($this->any())
            ->method('getApartmentGroups')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$groupMock]));

        $tokenMock = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $tokenMock->expects($this->any())->method('getUser')->willReturn($userMock);

        $tokenStorageMock = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class);
        $tokenStorageMock->expects($this->any())->method('getToken')->willReturn($tokenMock);

        // Ensure the hasAccess check evaluates to true
        $apartmentMock = $this->createMock(\App\Infrastructure\Persistence\Doctrine\Entity\Apartment::class);
        $criteriaCollectionMock = $this->createMock(\Doctrine\Common\Collections\ArrayCollection::class);
        $criteriaCollectionMock->expects($this->once())->method('isEmpty')->willReturn(false);

        $collectionMock = $this->createMock(\Doctrine\Common\Collections\ArrayCollection::class);
        $collectionMock->expects($this->once())->method('matching')->willReturn($criteriaCollectionMock);

        $apartmentMock->expects($this->once())
            ->method('getApartmentGroups')
            ->willReturn($collectionMock);

        $this->controller->edit($request, $apartmentMock);
    }

    public function testEditThrowsAccessDeniedExceptionForNonAdminWhenApartmentHasNoGroups(): void
    {
        $userMock = $this->createMock(\App\Infrastructure\Persistence\Doctrine\Entity\User::class);
        $userMock->expects($this->once())
            ->method('getApartmentGroups')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection());

        $tokenMock = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $tokenMock->expects($this->any())
            ->method('getUser')
            ->willReturn($userMock);

        $tokenStorageMock = $this->createMock(\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface::class);
        $tokenStorageMock->expects($this->any())
            ->method('getToken')
            ->willReturn($tokenMock);

        $authCheckerMock = $this->createMock(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class);
        $authCheckerMock->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->controller->setContainer($this->containerMock);

        $this->containerMock->expects($this->any())
            ->method('has')
            ->willReturnMap([
                ['security.authorization_checker', true],
                ['security.token_storage', true],
            ]);

        $this->containerMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['security.authorization_checker', $authCheckerMock],
                ['security.token_storage', $tokenStorageMock],
            ]);

        $request = new \Symfony\Component\HttpFoundation\Request();

        $apartmentMock = $this->createMock(\App\Infrastructure\Persistence\Doctrine\Entity\Apartment::class);
        $apartmentMock->expects($this->once())
            ->method('getApartmentGroups')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection());

        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);
        $this->expectExceptionMessage('No tienes permiso para editar este apartamento.');

        $this->controller->edit($request, $apartmentMock);
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
