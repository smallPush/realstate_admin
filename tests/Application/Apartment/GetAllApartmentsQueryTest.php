<?php

namespace App\Tests\Application\Apartment;

use App\Application\Apartment\Query\GetAllApartmentsQuery;
use App\Domain\Apartment\Apartment;
use App\Domain\Apartment\ApartmentRepositoryInterface;
use PHPUnit\Framework\TestCase;

class GetAllApartmentsQueryTest extends TestCase
{
    public function testExecuteReturnsAllApartments(): void
    {
        $repositoryMock = $this->createMock(ApartmentRepositoryInterface::class);

        $apartment1 = new Apartment('Piso 1', 'Calle 1', 1000, true, 1);
        $apartment2 = new Apartment('Piso 2', 'Calle 2', 1200, false, 2);

        $repositoryMock->expects($this->once())
            ->method('findAll')
            ->willReturn([$apartment1, $apartment2]);

        $query = new GetAllApartmentsQuery($repositoryMock);
        $result = $query->execute();

        $this->assertCount(2, $result);
        $this->assertSame($apartment1, $result[0]);
        $this->assertSame($apartment2, $result[1]);
    }
}
