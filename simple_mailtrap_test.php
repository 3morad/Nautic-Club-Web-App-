<?php

// This is a simple standalone test script that doesn't rely on Symfony
// It uses PHP's built-in mail function with custom SMTP settings

// Mailtrap credentials
$smtp_host = 'sandbox.smtp.mailtrap.io';
$smtp_port = 2525;
$smtp_user = 'ec9a061d3bd677';
$smtp_pass = '5f69f749a2d17e';

// Email details
$to = 'farouknakkach@gmail.com';
$subject = 'Test Email from Simple PHP Script';
$message = '<html><body><h1>Test Email</h1><p>This is a test email sent directly from PHP.</p></body></html>';

// Required headers
$headers = array(
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: noreply@nauticlub.com',
    'Reply-To: noreply@nauticlub.com'
);

// Configure PHP's mail settings for this script only
ini_set('SMTP', $smtp_host);
ini_set('smtp_port', $smtp_port);
ini_set('sendmail_from', 'noreply@nauticlub.com');
ini_set('smtp_username', $smtp_user);
ini_set('smtp_password', $smtp_pass);

echo "Attempting to send email to $to using Mailtrap...\n";

// Try to send the email
$result = mail($to, $subject, $message, implode("\r\n", $headers));

if ($result) {
    echo "Email sent successfully!\n";
    echo "Please check your Mailtrap inbox at https://mailtrap.io/inboxes\n";
} else {
    echo "Failed to send email.\n";
    echo "Error information: " . error_get_last()['message'] . "\n";
}

echo "\nNote: This script uses PHP's mail() function which may not work in all environments.\n";
echo "If this doesn't work, try using the Symfony command: php bin/console app:send-direct-email $to\n"; 