<?php

namespace App\Tests\Command;

use App\Command\CreateAdminCommand;
use App\Infrastructure\Persistence\Doctrine\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminCommandTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private EntityRepository $userRepository;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->userRepository = $this->createMock(EntityRepository::class);

        $this->entityManager->method('getRepository')
            ->with(User::class)
            ->willReturn($this->userRepository);

        $application = new Application();
        $application->add(new CreateAdminCommand($this->entityManager, $this->passwordHasher));

        $command = $application->find('app:create-admin');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithArguments(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_pass');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->commandTester->execute([
            'username' => 'admin_test',
            'password' => 'secret_pass',
        ]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Admin user successfully created/updated.', $this->commandTester->getDisplay());
    }

    public function testExecuteWithInteractivePassword(): void
    {
        $this->userRepository->method('findOneBy')->willReturn(null);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_pass');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->commandTester->setInputs(['secret_pass']);

        $this->commandTester->execute([
            'username' => 'admin_test',
        ], ['interactive' => true]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Admin user successfully created/updated.', $this->commandTester->getDisplay());
    }

    public function testExecuteFailsWithoutPasswordInNonInteractiveMode(): void
    {
        $this->commandTester->execute([
            'username' => 'admin_test',
        ], ['interactive' => false]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('The password cannot be empty', $this->commandTester->getDisplay());
    }
}
