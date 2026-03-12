<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Apartment\ApartmentRepositoryInterface;
use App\Domain\Apartment\Apartment as DomainApartment;
use App\Infrastructure\Persistence\Doctrine\Entity\Apartment as DoctrineApartment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DoctrineApartment>
 */
class ApartmentRepository extends ServiceEntityRepository implements ApartmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DoctrineApartment::class);
    }

    public function findById(int $id): ?DomainApartment
    {
        $doctrineApartment = $this->find($id);
        return $doctrineApartment ? $this->toDomain($doctrineApartment) : null;
    }

    /**
     * @return DomainApartment[]
     */
    public function findAll(): array
    {
        $doctrineApartments = parent::findAll();
        return array_map([$this, 'toDomain'], $doctrineApartments);
    }

    /**
     * @return DomainApartment[]
     */
    public function findAvailable(): array
    {
        $doctrineApartments = $this->findBy(['isAvailable' => true]);
        return array_map([$this, 'toDomain'], $doctrineApartments);
    }

    public function save(DomainApartment $domainApartment): void
    {
        $em = $this->getEntityManager();

        $doctrineApartment = null;
        if ($domainApartment->getId() !== null) {
            $doctrineApartment = $this->find($domainApartment->getId());
        }

        if (!$doctrineApartment) {
            $doctrineApartment = new DoctrineApartment();
        }

        $doctrineApartment->setName($domainApartment->getName());
        $doctrineApartment->setAddress($domainApartment->getAddress());
        $doctrineApartment->setPrice($domainApartment->getPrice());
        $doctrineApartment->setIsAvailable($domainApartment->isAvailable());
        $doctrineApartment->setDescription($domainApartment->getDescription());
        $doctrineApartment->setVapiSyncedAt($domainApartment->getVapiSyncedAt());

        $em->persist($doctrineApartment);
        $em->flush();
    }

    public function delete(DomainApartment $domainApartment): void
    {
        if ($domainApartment->getId() !== null) {
            $doctrineApartment = $this->find($domainApartment->getId());
            if ($doctrineApartment) {
                $em = $this->getEntityManager();
                $em->remove($doctrineApartment);
                $em->flush();
            }
        }
    }

    private function toDomain(DoctrineApartment $doctrineApartment): DomainApartment
    {
        return new DomainApartment(
            $doctrineApartment->getName(),
            $doctrineApartment->getAddress(),
            $doctrineApartment->getPrice(),
            $doctrineApartment->isAvailable(),
            $doctrineApartment->getId(),
            $doctrineApartment->getDescription(),
            $doctrineApartment->getVapiSyncedAt()
        );
    }

}
