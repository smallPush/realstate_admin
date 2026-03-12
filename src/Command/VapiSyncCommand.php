<?php

namespace App\Command;

use App\Application\Apartment\Command\SyncKnowledgeBaseCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:vapi-sync',
    description: 'Synchronizes available apartments with Vapi Knowledge Base.',
)]
class VapiSyncCommand extends Command
{
    public function __construct(
        private readonly SyncKnowledgeBaseCommand $syncKnowledgeBaseCommand,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Vapi Knowledge Base Synchronization');

        try {
            $io->info('Starting synchronization process...');
            $this->syncKnowledgeBaseCommand->execute();
            $io->success('Synchronization completed successfully!');
        } catch (\Throwable $e) {
            $io->error(sprintf('Synchronization failed: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
