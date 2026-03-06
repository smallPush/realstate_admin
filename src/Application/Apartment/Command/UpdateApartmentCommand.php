<?php

namespace App\Application\Apartment\Command;

use App\Domain\Apartment\Apartment;
use App\Domain\Apartment\ApartmentRepositoryInterface;

class UpdateApartmentCommand
{
    private ApartmentRepositoryInterface $apartmentRepository;

    public function __construct(ApartmentRepositoryInterface $apartmentRepository)
    {
        $this->apartmentRepository = $apartmentRepository;
    }

    public function execute(Apartment $apartment): void
    {
        $this->apartmentRepository->save($apartment);
    }
}
