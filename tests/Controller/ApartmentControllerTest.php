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
}
