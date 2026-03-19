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

    public function execute(?array $groupIds = null): array
    {
        if ($groupIds !== null) {
            return $this->apartmentRepository->findByGroupIds($groupIds);
        }

        return $this->apartmentRepository->findAll();
    }
}
