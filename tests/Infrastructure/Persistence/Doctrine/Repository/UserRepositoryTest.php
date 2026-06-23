<?php

namespace App\Tests\Infrastructure\Persistence\Doctrine\Repository;

use App\Infrastructure\Persistence\Doctrine\Entity\User;
use App\Infrastructure\Persistence\Doctrine\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class UserRepositoryTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(ManagerRegistry::class);

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($this->entityManager);

        // We need to set the $name property on the metadata
        $classMetadata = new ClassMetadata(User::class);
        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(User::class)
            ->willReturn($classMetadata);

        $this->repository = new UserRepository($registry);
    }

    public function testSaveWithoutFlush(): void
    {
        $user = new User();

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->repository->save($user, false);
    }

    public function testSaveWithFlush(): void
    {
        $user = new User();

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->repository->save($user, true);
    }
}
