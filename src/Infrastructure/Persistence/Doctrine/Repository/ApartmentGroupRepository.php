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

    /**
     * @return int[]
     */
    public function getUserApartmentGroupIds(int $userId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            WITH RECURSIVE group_tree AS (
                SELECT ag.id
                FROM apartment_group ag
                INNER JOIN user_apartment_group uag ON uag.apartment_group_id = ag.id
                WHERE uag.user_id = :userId

                UNION ALL

                SELECT ag.id
                FROM apartment_group ag
                INNER JOIN group_tree gt ON ag.parent_id = gt.id
            )
            SELECT DISTINCT id FROM group_tree
        ';

        $stmt = $conn->executeQuery($sql, ['userId' => $userId]);

        // Ensure integer mapping
        return array_map('intval', $stmt->fetchFirstColumn());
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
