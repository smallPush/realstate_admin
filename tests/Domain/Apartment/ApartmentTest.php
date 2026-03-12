<?php

namespace App\Tests\Domain\Apartment;

use App\Domain\Apartment\Apartment;
use PHPUnit\Framework\TestCase;

class ApartmentTest extends TestCase
{
    public function testConstructorAssignsPropertiesWithDefaults(): void
    {
        $apartment = new Apartment('Sunset Villa', '123 Beach Rd', 150000);

        $this->assertSame('Sunset Villa', $apartment->getName());
        $this->assertSame('123 Beach Rd', $apartment->getAddress());
        $this->assertSame(150000, $apartment->getPrice());
        $this->assertTrue($apartment->isAvailable());
        $this->assertNull($apartment->getId());
    }

    public function testConstructorAssignsAllProperties(): void
    {
        $apartment = new Apartment(
            'Mountain View',
            '456 Hill St',
            200000,
            false,
            42
        );

        $this->assertSame('Mountain View', $apartment->getName());
        $this->assertSame('456 Hill St', $apartment->getAddress());
        $this->assertSame(200000, $apartment->getPrice());
        $this->assertFalse($apartment->isAvailable());
        $this->assertSame(42, $apartment->getId());
    }

    public function testSettersUpdateProperties(): void
    {
        $apartment = new Apartment('Initial Name', 'Initial Address', 100000);

        $apartment->setName('New Name');
        $this->assertSame('New Name', $apartment->getName());

        $apartment->setAddress('New Address');
        $this->assertSame('New Address', $apartment->getAddress());

        $apartment->setPrice(250000);
        $this->assertSame(250000, $apartment->getPrice());

        $apartment->setIsAvailable(false);
        $this->assertFalse($apartment->isAvailable());
    }
}
