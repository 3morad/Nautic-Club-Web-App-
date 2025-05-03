<?php

namespace App\Command;

use App\Entity\CreateMessengerMessagesTable;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-messenger-table',
    description: 'Creates the messenger_messages table for email queuing',
)]
class CreateMessengerTableCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $sql = CreateMessengerMessagesTable::getCreateTableSQL();
            $this->connection->executeStatement($sql);
            
            $io->success('Messenger messages table created successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to create messenger_messages table: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 