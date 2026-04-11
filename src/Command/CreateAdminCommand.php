<?php

namespace App\Command;

use App\Infrastructure\Persistence\Doctrine\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creates a new admin user or updates the password if it already exists.',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the admin user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The password of the admin user')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getArgument('password')) {
            $password = $io->askHidden('Please enter the password for the admin user');
            if ($password) {
                $input->setArgument('password', $password);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        if (empty($password)) {
            $io->error('The password cannot be empty. Please provide it as an argument or in interactive mode.');
            return Command::FAILURE;
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['username' => $username]);

        if (!$user) {
            $user = new User();
            $user->setUsername($username);
            $io->note(sprintf('Creating new admin user: %s', $username));
        } else {
            $io->note(sprintf('Admin user already exists. Updating password for: %s', $username));
        }

        $user->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Admin user successfully created/updated.');

        return Command::SUCCESS;
    }
}
