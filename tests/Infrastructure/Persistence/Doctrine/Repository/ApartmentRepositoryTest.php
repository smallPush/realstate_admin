<?php

namespace App\Tests\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Apartment\Apartment as DomainApartment;
use App\Infrastructure\Persistence\Doctrine\Entity\Apartment as DoctrineApartment;
use App\Infrastructure\Persistence\Doctrine\Repository\ApartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ApartmentRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?ApartmentRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(DoctrineApartment::class);

        // Ensure database schema is created for the tests
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Drop and recreate schema to ensure clean state
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }

    public function testFindByIdReturnsDomainApartmentWhenFound(): void
    {
        $doctrineApartment = new DoctrineApartment();
        $doctrineApartment->setName('Test Apt');
        $doctrineApartment->setAddress('123 Test St');
        $doctrineApartment->setPrice(1000);
        $doctrineApartment->setIsAvailable(true);
        $doctrineApartment->setDescription('Test Description');

        $this->entityManager->persist($doctrineApartment);
        $this->entityManager->flush();

        $id = $doctrineApartment->getId();
        $this->assertNotNull($id);

        $domainApartment = $this->repository->findById($id);

        $this->assertNotNull($domainApartment);
        $this->assertEquals($id, $domainApartment->getId());
        $this->assertEquals('Test Apt', $domainApartment->getName());
        $this->assertEquals('123 Test St', $domainApartment->getAddress());
        $this->assertEquals(1000, $domainApartment->getPrice());
        $this->assertTrue($domainApartment->isAvailable());
        $this->assertEquals('Test Description', $domainApartment->getDescription());
    }

    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $domainApartment = $this->repository->findById(999);

        $this->assertNull($domainApartment);
    }

    public function testFindAllReturnsArrayOfDomainApartments(): void
    {
        $doctrineApartment1 = new DoctrineApartment();
        $doctrineApartment1->setName('Apt 1');
        $doctrineApartment1->setAddress('Addr 1');
        $doctrineApartment1->setPrice(1000);
        $doctrineApartment1->setIsAvailable(true);

        $doctrineApartment2 = new DoctrineApartment();
        $doctrineApartment2->setName('Apt 2');
        $doctrineApartment2->setAddress('Addr 2');
        $doctrineApartment2->setPrice(2000);
        $doctrineApartment2->setIsAvailable(false);

        $this->entityManager->persist($doctrineApartment1);
        $this->entityManager->persist($doctrineApartment2);
        $this->entityManager->flush();

        $domainApartments = $this->repository->findAll();

        $this->assertCount(2, $domainApartments);
        $this->assertInstanceOf(DomainApartment::class, $domainApartments[0]);
        $this->assertEquals('Apt 1', $domainApartments[0]->getName());
        $this->assertInstanceOf(DomainApartment::class, $domainApartments[1]);
        $this->assertEquals('Apt 2', $domainApartments[1]->getName());
    }

    public function testFindAvailableReturnsArrayOfDomainApartments(): void
    {
        $doctrineApartment1 = new DoctrineApartment();
        $doctrineApartment1->setName('Apt 1');
        $doctrineApartment1->setAddress('Addr 1');
        $doctrineApartment1->setPrice(1000);
        $doctrineApartment1->setIsAvailable(true);

        $doctrineApartment2 = new DoctrineApartment();
        $doctrineApartment2->setName('Apt 2');
        $doctrineApartment2->setAddress('Addr 2');
        $doctrineApartment2->setPrice(2000);
        $doctrineApartment2->setIsAvailable(false);

        $this->entityManager->persist($doctrineApartment1);
        $this->entityManager->persist($doctrineApartment2);
        $this->entityManager->flush();

        $domainApartments = $this->repository->findAvailable();

        $this->assertCount(1, $domainApartments);
        $this->assertInstanceOf(DomainApartment::class, $domainApartments[0]);
        $this->assertEquals('Apt 1', $domainApartments[0]->getName());
    }

    public function testSaveNewApartment(): void
    {
        $domainApartment = new DomainApartment('New Apt', 'New Addr', 1500, true);

        $this->repository->save($domainApartment);

        // Use standard Doctrine Repository to check the DB directly to ensure save worked
        $doctrineRepository = $this->entityManager->getRepository(DoctrineApartment::class);
        $savedApartments = $doctrineRepository->findAll();

        $this->assertCount(1, $savedApartments);
        $this->assertEquals('New Apt', $savedApartments[0]->getName());
        $this->assertEquals('New Addr', $savedApartments[0]->getAddress());
        $this->assertEquals(1500, $savedApartments[0]->getPrice());
        $this->assertTrue($savedApartments[0]->isAvailable());
    }

    public function testSaveExistingApartment(): void
    {
        $doctrineApartment = new DoctrineApartment();
        $doctrineApartment->setName('Old Apt');
        $doctrineApartment->setAddress('Old Addr');
        $doctrineApartment->setPrice(1000);
        $doctrineApartment->setIsAvailable(true);

        $this->entityManager->persist($doctrineApartment);
        $this->entityManager->flush();
        $this->entityManager->clear(); // Detach all entities

        $id = $doctrineApartment->getId();

        $domainApartment = new DomainApartment('Updated Apt', 'Updated Addr', 2000, false, $id);

        $this->repository->save($domainApartment);
        $this->entityManager->clear(); // Detach all entities

        $updatedDoctrineApartment = $this->entityManager->find(DoctrineApartment::class, $id);

        $this->assertNotNull($updatedDoctrineApartment);
        $this->assertEquals('Updated Apt', $updatedDoctrineApartment->getName());
        $this->assertEquals('Updated Addr', $updatedDoctrineApartment->getAddress());
        $this->assertEquals(2000, $updatedDoctrineApartment->getPrice());
        $this->assertFalse($updatedDoctrineApartment->isAvailable());
    }

    public function testDeleteExistingApartment(): void
    {
        $doctrineApartment = new DoctrineApartment();
        $doctrineApartment->setName('Apt to delete');
        $doctrineApartment->setAddress('Addr');
        $doctrineApartment->setPrice(1000);
        $doctrineApartment->setIsAvailable(true);

        $this->entityManager->persist($doctrineApartment);
        $this->entityManager->flush();
        $this->entityManager->clear(); // Detach all entities

        $id = $doctrineApartment->getId();
        $domainApartment = new DomainApartment('Apt to delete', 'Addr', 1000, true, $id);

        $this->repository->delete($domainApartment);
        $this->entityManager->clear();

        $deletedApartment = $this->entityManager->find(DoctrineApartment::class, $id);
        $this->assertNull($deletedApartment);
    }

    public function testDeleteNonExistingApartmentDoesNotThrowError(): void
    {
        $domainApartment = new DomainApartment('Apt to delete', 'Addr', 1000, true, 999);

        // Should not throw any exception when ID doesn't exist
        $this->repository->delete($domainApartment);

        $this->assertTrue(true); // Explicit assertion to show the test passed without exceptions
    }

    public function testDeleteApartmentWithoutIdDoesNotThrowError(): void
    {
        $domainApartment = new DomainApartment('Apt to delete', 'Addr', 1000, true);

        // Should not throw any exception when no ID is provided
        $this->repository->delete($domainApartment);

        $this->assertTrue(true); // Explicit assertion to show the test passed without exceptions
    }
}
