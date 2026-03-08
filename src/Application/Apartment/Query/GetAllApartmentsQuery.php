<?php

namespace App\Application\Apartment\Query;

use App\Domain\Apartment\ApartmentRepositoryInterface;

class GetAllApartmentsQuery
{
    private ApartmentRepositoryInterface $apartmentRepository;

    public function __construct(ApartmentRepositoryInterface $apartmentRepository)
    {
        $this->apartmentRepository = $apartmentRepository;
    }

    public function execute(): array
    {
        return $this->apartmentRepository->findAll();
    }
}
