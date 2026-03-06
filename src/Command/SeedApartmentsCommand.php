<?php

namespace App\Command;

use App\Infrastructure\Persistence\Doctrine\Entity\Apartment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-apartments',
    description: 'Seeds the database with some example apartments for Vapi',
)]
class SeedApartmentsCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $apartments = [
            ['name' => 'Piso Centro', 'address' => 'Calle Mayor 1', 'price' => 1200, 'isAvailable' => true],
            ['name' => 'Ático con vistas', 'address' => 'Avenida de América 23', 'price' => 1800, 'isAvailable' => true],
            ['name' => 'Estudio Económico', 'address' => 'Callejón del Gato 5', 'price' => 600, 'isAvailable' => true],
            ['name' => 'Chalet Afueras', 'address' => 'Urbanización El Bosque 10', 'price' => 2500, 'isAvailable' => false],
            ['name' => 'Piso Universitario', 'address' => 'Avenida Complutense 45', 'price' => 800, 'isAvailable' => true],
        ];

        foreach ($apartments as $aptData) {
            $apartment = new Apartment();
            $apartment->setName($aptData['name']);
            $apartment->setAddress($aptData['address']);
            $apartment->setPrice($aptData['price']);
            $apartment->setAvailable($aptData['isAvailable']);

            $this->entityManager->persist($apartment);
        }

        $this->entityManager->flush();

        $io->success('Se han insertado varios pisos de ejemplo en la base de datos.');

        return Command::SUCCESS;
    }
}
