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
     * @param int[] $groupIds
     * @return Apartment[]
     */
    public function findByGroupIds(array $groupIds): array;

    /**
     * @return Apartment[]
     */
    public function findAvailable(): array;

    public function save(Apartment $apartment): void;

    /**
     * @param Apartment[] $apartments
     */
    public function saveAll(array $apartments): void;

    public function delete(Apartment $apartment): void;
}
