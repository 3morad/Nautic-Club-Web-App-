<?php

namespace App\Service;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class DirectEmailService
{
    private string $dsn;

    public function __construct(string $mailerDsn = null)
    {
        // Use provided DSN or default to the Mailtrap credentials
        $this->dsn = $mailerDsn ?? 'smtp://ec9a061d3bd677:5f69f749a2d17e@sandbox.smtp.mailtrap.io:2525';
    }

    public function sendEmail(string $to, string $subject, string $text, string $html = null): void
    {
        try {
            $email = (new Email())
                ->from('noreply@nauticlub.com')
                ->to($to)
                ->subject($subject)
                ->text($text);

            if ($html) {
                $email->html($html);
            }

            // Create transport and mailer directly without using messenger
            $transport = Transport::fromDsn($this->dsn);
            $mailer = new Mailer($transport);
            
            // Send the email
            $mailer->send($email);
            
            // If we reach here, email was sent successfully
            echo "Email sent successfully to: $to\n";
            echo "Mailtrap should have received it. Check your Mailtrap inbox.\n";
            
        } catch (TransportExceptionInterface $e) {
            // Log detailed transport error information
            echo "Transport Error: " . $e->getMessage() . "\n";
            echo "DSN used: " . $this->getMaskedDsn() . "\n";
            throw $e;
        } catch (\Exception $e) {
            // Log general error information
            echo "General Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    /**
     * Returns a masked version of the DSN for safe logging
     */
    private function getMaskedDsn(): string
    {
        // Simple masking to protect credentials in logs
        $parts = parse_url($this->dsn);
        if (isset($parts['user']) && isset($parts['pass'])) {
            return str_replace(
                $parts['user'] . ':' . $parts['pass'] . '@',
                $parts['user'][0] . '***:' . $parts['pass'][0] . '***@',
                $this->dsn
            );
        }
        return $this->dsn;
    }

    public function sendWelcomeEmail(string $to, string $username): void
    {
        $subject = 'Welcome to NautiClub!';
        $text = "Hello $username,\n\nWelcome to NautiClub! Your account has been created successfully.\n\nRegards,\nThe NautiClub Team";
        $html = "
            <h1>Welcome to NautiClub!</h1>
            <p>Hello $username,</p>
            <p>Welcome to NautiClub! Your account has been created successfully.</p>
            <p>Regards,<br>The NautiClub Team</p>
        ";

        $this->sendEmail($to, $subject, $text, $html);
    }

    public function sendPasswordResetEmail(string $to, string $username, string $resetLink): void
    {
        $subject = 'Password Reset Request';
        $text = "Hello $username,\n\nYou requested a password reset. Click the following link to reset your password: $resetLink\n\nIf you did not request this, please ignore this email.\n\nRegards,\nThe NautiClub Team";
        $html = "
            <h1>Password Reset Request</h1>
            <p>Hello $username,</p>
            <p>You requested a password reset. Click the following link to reset your password:</p>
            <p><a href=\"$resetLink\">Reset Password</a></p>
            <p>If you did not request this, please ignore this email.</p>
            <p>Regards,<br>The NautiClub Team</p>
        ";

        $this->sendEmail($to, $subject, $text, $html);
    }
} 