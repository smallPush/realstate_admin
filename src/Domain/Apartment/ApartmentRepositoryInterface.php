<?php

namespace App\Domain\Apartment;

interface ApartmentRepositoryInterface
{
    public function findById(int $id): ?Apartment;

    /**
     * @return Apartment[]
     */
    public function findAll(): array;

    /**
     * @return Apartment[]
     */
    public function findAvailable(): array;

    public function save(Apartment $apartment): void;

    public function delete(Apartment $apartment): void;
}
