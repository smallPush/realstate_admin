<?php

namespace App\Tests\Command;

use App\Command\SeedApartmentsCommand;
use App\Infrastructure\Persistence\Doctrine\Entity\Apartment;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class SeedApartmentsCommandTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $application = new Application();
        $application->addCommand(new SeedApartmentsCommand($this->entityManager));

        $command = $application->find('app:seed-apartments');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteSeedsApartmentsSuccessfully(): void
    {
        $this->entityManager->expects($this->exactly(5))
            ->method('persist')
            ->with($this->isInstanceOf(Apartment::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->commandTester->execute([]);

        $this->commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString(
            'Se han insertado varios pisos de ejemplo en la base de datos.',
            $this->commandTester->getDisplay()
        );
    }
}
