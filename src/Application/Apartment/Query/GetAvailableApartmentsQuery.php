<?php

namespace App\Application\Apartment\Query;

use App\Domain\Apartment\ApartmentRepositoryInterface;

class GetAvailableApartmentsQuery
{
    private ApartmentRepositoryInterface $apartmentRepository;

    public function __construct(ApartmentRepositoryInterface $apartmentRepository)
    {
        $this->apartmentRepository = $apartmentRepository;
    }

    public function execute(): array
    {
        return $this->apartmentRepository->findAvailable();
    }
}
