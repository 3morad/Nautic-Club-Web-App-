<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TestEmailCommand extends Command
{
    protected static $defaultName = 'app:test-email';
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function configure()
    {
        $this->setDescription('Test email sending functionality');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('noreply@nautic-club.com')
            ->to('your-email@example.com') // Replace with your email
            ->subject('Test Email')
            ->text('This is a test email from your Symfony application.');

        try {
            $this->mailer->send($email);
            $output->writeln('Test email sent successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Error sending email: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 