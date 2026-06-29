<?php

namespace App\Tests\Infrastructure\Persistence\Doctrine\Entity;

use App\Infrastructure\Persistence\Doctrine\Entity\ApartmentGroup;
use App\Infrastructure\Persistence\Doctrine\Entity\User;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testConstructorInitializesCollections(): void
    {
        $user = new User();

        $this->assertInstanceOf(Collection::class, $user->getApartmentGroups());
        $this->assertCount(0, $user->getApartmentGroups());
    }

    public function testGetAndSetUsername(): void
    {
        $user = new User();
        $this->assertNull($user->getUsername());

        $user->setUsername('testuser');
        $this->assertSame('testuser', $user->getUsername());

        // getUserIdentifier should return the username
        $this->assertSame('testuser', $user->getUserIdentifier());
    }

    public function testGetAndSetPassword(): void
    {
        $user = new User();
        $this->assertNull($user->getPassword());

        $user->setPassword('secretpassword');
        $this->assertSame('secretpassword', $user->getPassword());

        $user->setPassword(null);
        $this->assertNull($user->getPassword());
    }

    public function testGetAndSetRoles(): void
    {
        $user = new User();
        // By default, every user has ROLE_USER
        $this->assertSame(['ROLE_USER'], $user->getRoles());

        $user->setRoles(['ROLE_ADMIN']);
        // ROLE_USER should always be included and unique
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());

        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        // Currently empty but testing it exists and doesn't throw
        $user->eraseCredentials();
        $this->assertTrue(true);
    }

    public function testAddAndRemoveApartmentGroup(): void
    {
        $user = new User();
        $apartmentGroup = $this->createMock(ApartmentGroup::class);

        $user->addApartmentGroup($apartmentGroup);
        $this->assertCount(1, $user->getApartmentGroups());
        $this->assertTrue($user->getApartmentGroups()->contains($apartmentGroup));

        // Adding the same group twice should only add it once
        $user->addApartmentGroup($apartmentGroup);
        $this->assertCount(1, $user->getApartmentGroups());

        $user->removeApartmentGroup($apartmentGroup);
        $this->assertCount(0, $user->getApartmentGroups());
        $this->assertFalse($user->getApartmentGroups()->contains($apartmentGroup));
    }

    public function testGetIdReturnsNullInitially(): void
    {
        $user = new User();
        $this->assertNull($user->getId());
    }
}
