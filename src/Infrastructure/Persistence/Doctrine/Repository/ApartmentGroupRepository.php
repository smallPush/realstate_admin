<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\ApartmentGroup\ApartmentGroup as DomainApartmentGroup;
use App\Domain\ApartmentGroup\ApartmentGroupRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\ApartmentGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApartmentGroup>
 *
 * @method ApartmentGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method ApartmentGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method ApartmentGroup[]    findAll()
 * @method ApartmentGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApartmentGroupRepository extends ServiceEntityRepository implements ApartmentGroupRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApartmentGroup::class);
    }

    /**
     * @return DomainApartmentGroup[]
     */
    public function findAllDomain(): array
    {
        $entities = $this->findAll();

        return array_map(function (ApartmentGroup $entity) {
            return $this->toDomain($entity);
        }, $entities);
    }

    public function findById(int $id): ?DomainApartmentGroup
    {
        $entity = $this->find($id);
        if (!$entity) {
            return null;
        }

        return $this->toDomain($entity);
    }

    public function save(DomainApartmentGroup $apartmentGroup): void
    {
        $this->persistDomain($apartmentGroup);
        $this->getEntityManager()->flush();
    }

    public function delete(DomainApartmentGroup $apartmentGroup): void
    {
        if ($apartmentGroup->getId() !== null) {
            $entity = $this->find($apartmentGroup->getId());
            if ($entity) {
                $this->getEntityManager()->remove($entity);
                $this->getEntityManager()->flush();
            }
        }
    }

    private function persistDomain(DomainApartmentGroup $domainGroup): ApartmentGroup
    {
        $entity = null;
        if ($domainGroup->getId() !== null) {
            $entity = $this->find($domainGroup->getId());
        }

        if (!$entity) {
            $entity = new ApartmentGroup();
        }

        $entity->setName($domainGroup->getName());

        if ($domainGroup->getParent() !== null) {
            $parentEntity = clone $this->persistDomain($domainGroup->getParent()); // avoid circular logic in saving temporarily
            $entity->setParent($parentEntity);
        } else {
            $entity->setParent(null);
        }

        $this->getEntityManager()->persist($entity);
        return $entity;
    }

    private function toDomain(ApartmentGroup $entity): DomainApartmentGroup
    {
        $parent = null;
        if ($entity->getParent() !== null) {
            // Need to handle infinite loop, just return null parent for now, or fetch eagerly
            // It might be better to just leave parent as basic reference to avoid deep recursion
            $parent = new DomainApartmentGroup(
                $entity->getParent()->getName(),
                null,
                $entity->getParent()->getId()
            );
        }

        return new DomainApartmentGroup(
            $entity->getName(),
            $parent,
            $entity->getId()
        );
    }
}
