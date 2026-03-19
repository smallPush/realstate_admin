<?php

namespace App\Domain\ApartmentGroup;

interface ApartmentGroupRepositoryInterface
{
    /**
     * @return ApartmentGroup[]
     */
    public function findAllDomain(): array;

    public function findById(int $id): ?ApartmentGroup;

    public function save(ApartmentGroup $apartmentGroup): void;

    public function delete(ApartmentGroup $apartmentGroup): void;
}
