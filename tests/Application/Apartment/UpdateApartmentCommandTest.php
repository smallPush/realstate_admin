<?php

namespace App\Tests\Application\Apartment;

use App\Application\Apartment\Command\UpdateApartmentCommand;
use App\Domain\Apartment\Apartment;
use App\Domain\Apartment\ApartmentRepositoryInterface;
use PHPUnit\Framework\TestCase;

class UpdateApartmentCommandTest extends TestCase
{
    public function testExecuteSavesApartment(): void
    {
        $repositoryMock = $this->createMock(ApartmentRepositoryInterface::class);
        $apartment = new Apartment('Piso Centro', 'Calle Mayor', 1200, true, 1);

        $repositoryMock->expects($this->once())
            ->method('save')
            ->with($this->equalTo($apartment));

        $command = new UpdateApartmentCommand($repositoryMock);
        $command->execute($apartment);
    }
}
