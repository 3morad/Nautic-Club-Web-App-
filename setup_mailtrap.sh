#!/bin/bash

# Create or update .env.local with Mailtrap credentials
echo "Adding Mailtrap credentials to .env.local..."
echo "###> symfony/mailer ###" >> .env.local
echo "MAILER_DSN=smtp://ec9a061d3bd677:5f69f749a2d17e@sandbox.smtp.mailtrap.io:2525" >> .env.local
echo "###< symfony/mailer ###" >> .env.local

# Install Symfony Mailer if not already installed
echo "Installing Symfony Mailer..."
composer require symfony/mailer

# Clear cache
echo "Clearing cache..."
php bin/console cache:clear

echo "Done! Mailtrap is now configured."
echo "Test sending an email with: php bin/console app:send-test-email test@example.com" 