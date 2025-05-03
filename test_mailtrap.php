<?php

// Standalone script to test Mailtrap integration without using Symfony

// Set your Mailtrap credentials
$username = 'ec9a061d3bd677';
$password = '5f69f749a2d17e';
$host = 'sandbox.smtp.mailtrap.io';
$port = 2525;

// Email content
$to = 'test@example.com'; // Change this to your email
$subject = 'Test Email from NautiClub';
$message = 'This is a test email sent using Mailtrap to verify the email functionality works.';
$headers = [
    'From' => 'noreply@nauticlub.com',
    'Reply-To' => 'noreply@nauticlub.com',
    'MIME-Version' => '1.0',
    'Content-type' => 'text/html; charset=iso-8859-1'
];

// Create a custom header for mail()
$header = '';
foreach ($headers as $key => $value) {
    $header .= "$key: $value\r\n";
}

// Output instructions for setup
echo "=== Mailtrap Test Setup ===\n\n";
echo "To configure your Symfony app with Mailtrap:\n";
echo "1. Run: composer require symfony/mailer\n";
echo "2. Edit your .env file and add:\n";
echo "   MAILER_DSN=smtp://ec9a061d3bd677:5f69f749a2d17e@sandbox.smtp.mailtrap.io:2525\n\n";
echo "3. Clear the cache: php bin/console cache:clear\n\n";
echo "To test if it works run: php bin/console app:send-test-email your@email.com\n\n";

// Try sending the email using native PHP
echo "Attempting to send a test email using PHP's mail() function...\n";
if (mail($to, $subject, $message, $header)) {
    echo "Email sent successfully! Check your Mailtrap inbox.\n";
} else {
    echo "Failed to send email. This is expected in some environments where mail() doesn't work.\n";
    echo "But don't worry - Symfony's Mailer will use SMTP directly and should work fine.\n";
}

echo "\nFollow the steps in MAILTRAP_SETUP.md for complete instructions.\n";
echo "Your Mailtrap credentials have been set up as:\n";
echo "- Username: $username\n";
echo "- Password: $password\n";
echo "- Host: $host\n";
echo "- Port: $port\n"; 