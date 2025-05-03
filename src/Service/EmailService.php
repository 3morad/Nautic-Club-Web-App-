<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendEmail(string $to, string $subject, string $text, string $html = null): void
    {
        $email = (new Email())
            ->from('noreply@nauticlub.com')
            ->to($to)
            ->subject($subject)
            ->text($text);

        if ($html) {
            $email->html($html);
        }

        $this->mailer->send($email);
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