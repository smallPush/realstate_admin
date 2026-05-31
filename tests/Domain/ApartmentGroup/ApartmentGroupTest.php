<?php

namespace App\Tests\Domain\ApartmentGroup;

use App\Domain\ApartmentGroup\ApartmentGroup;
use PHPUnit\Framework\TestCase;

final class ApartmentGroupTest extends TestCase
{
    public function testConstructorAssignsProperties(): void
    {
        $name = 'Root Group';
        $group = new ApartmentGroup($name);

        $this->assertSame($name, $group->getName());
        $this->assertNull($group->getParent());
        $this->assertNull($group->getId());
    }

    public function testConstructorAssignsAllProperties(): void
    {
        $parent = new ApartmentGroup('Parent Group');
        $name = 'Child Group';
        $id = 123;

        $group = new ApartmentGroup($name, $parent, $id);

        $this->assertSame($name, $group->getName());
        $this->assertSame($parent, $group->getParent());
        $this->assertSame($id, $group->getId());
    }
}
