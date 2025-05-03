<?php

namespace App\Command;

use App\Service\DirectEmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-direct-email',
    description: 'Send a test email using direct mailer (no messenger)',
)]
class SendDirectEmailCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email address to send to');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $io->note(sprintf('Sending test email to: %s', $email));

        try {
            $emailService = new DirectEmailService();
            $emailService->sendEmail(
                $email,
                'Test Email from NautiClub',
                'This is a test email from the NautiClub application.',
                '<h1>Test Email</h1><p>This is a test email from the <strong>NautiClub</strong> application.</p>'
            );

            $io->success('Email was sent successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to send email: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 