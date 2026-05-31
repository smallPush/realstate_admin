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
        return array_map(fn(DoctrineApartment $a) => $this->toDomain($a), $doctrineApartments);
    }

    /**
     * @param int[] $groupIds
     * @return DomainApartment[]
     */
    public function findByGroupIds(array $groupIds): array
    {
        if (empty($groupIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('a')
            ->join('a.apartmentGroups', 'g')
            ->where('g.id IN (:groupIds)')
            ->setParameter('groupIds', $groupIds);

        $doctrineApartments = $qb->getQuery()->getResult();
        return array_map(fn(DoctrineApartment $a) => $this->toDomain($a), $doctrineApartments);
    }

    /**
     * @return DomainApartment[]
     */
    public function findAvailable(): array
    {
        $doctrineApartments = $this->findBy(['isAvailable' => true]);
        return array_map(fn(DoctrineApartment $a) => $this->toDomain($a), $doctrineApartments);
    }

    public function save(DomainApartment $domainApartment): void
    {
        $this->persistDomain($domainApartment);
        $this->getEntityManager()->flush();
    }

    public function saveAll(array $apartments): void
    {
        $ids = array_filter(array_map(fn(DomainApartment $a) => $a->getId(), $apartments));

        $existingEntities = [];
        if (!empty($ids)) {
            $entities = $this->findBy(['id' => $ids]);
            foreach ($entities as $entity) {
                $existingEntities[$entity->getId()] = $entity;
            }
        }

        foreach ($apartments as $apartment) {
            $doctrineApartment = $apartment->getId() !== null ? ($existingEntities[$apartment->getId()] ?? null) : null;
            $this->persistDomain($apartment, $doctrineApartment);
        }
        $this->getEntityManager()->flush();
    }

    private function persistDomain(DomainApartment $domainApartment, ?DoctrineApartment $doctrineApartment = null): void
    {
        $em = $this->getEntityManager();

        if ($doctrineApartment === null && $domainApartment->getId() !== null) {
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
