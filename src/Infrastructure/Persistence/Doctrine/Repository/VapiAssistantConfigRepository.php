<?php

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Apartment\VapiAssistantConfig as DomainVapiAssistantConfig;
use App\Domain\Apartment\VapiAssistantConfigRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\VapiAssistantConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VapiAssistantConfig>
 */
class VapiAssistantConfigRepository extends ServiceEntityRepository implements VapiAssistantConfigRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VapiAssistantConfig::class);
    }

    public function getConfig(): ?DomainVapiAssistantConfig
    {
        $entity = $this->findOneBy([]);
        if (!$entity) {
            return null;
        }

        $domainConfig = new DomainVapiAssistantConfig(
            $entity->getPrompt(),
            $entity->getFirstMessage(),
            $entity->getTimeLimit(),
            $entity->getUpdatedAt()
        );

        // Map internal ID if needed, using reflection
        $reflection = new \ReflectionClass($domainConfig);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($domainConfig, (string) $entity->getId());

        return $domainConfig;
    }

    public function save(DomainVapiAssistantConfig $domainConfig): void
    {
        $entity = $this->findOneBy([]);
        if (!$entity) {
            $entity = new VapiAssistantConfig();
            $this->getEntityManager()->persist($entity);
        }

        $entity->setPrompt($domainConfig->getPrompt());
        $entity->setFirstMessage($domainConfig->getFirstMessage());
        $entity->setTimeLimit($domainConfig->getTimeLimit());
        $entity->setUpdatedAt($domainConfig->getUpdatedAt());

        $this->getEntityManager()->flush();
    }
}
