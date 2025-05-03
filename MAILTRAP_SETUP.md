# Setting up Mailtrap with NautiClub

Follow these steps to configure Mailtrap for email testing in your application:

## 1. Create a Mailtrap Account

1. Go to [Mailtrap.io](https://mailtrap.io/) and sign up for a free account.
2. Login to your account.

## 2. Get Your Mailtrap Credentials

1. In your Mailtrap dashboard, go to "Email Testing" and select your inbox (or create a new one).
2. Click on "SMTP Settings" and select "Symfony 5+" from the integrations dropdown.
3. You'll see a configuration like:
   ```
   MAILER_DSN=smtp://username:password@sandbox.smtp.mailtrap.io:2525
   ```

## 3. Update Your .env File

1. Open your `.env` file in the root of your project.
2. Add or update the following section:
   ```
   ###> symfony/mailer ###
   MAILER_DSN=smtp://your_username:your_password@sandbox.smtp.mailtrap.io:2525
   ###< symfony/mailer ###
   ```
   Replace `your_username` and `your_password` with the values from Mailtrap.

## 4. Test Sending an Email

Run the following command to test sending an email:

```bash
php bin/console app:send-test-email your-email@example.com
```

You should see a success message, and the email should appear in your Mailtrap inbox.

## 5. Using the Email Service in Your Controllers

You can now use the EmailService in your controllers:

```php
use App\Service\EmailService;

// ...

public function someAction(EmailService $emailService)
{
    $emailService->sendEmail(
        'recipient@example.com',
        'Subject',
        'Plain text content',
        '<h1>HTML content</h1>'
    );
    
    // Or use the predefined email templates:
    $emailService->sendWelcomeEmail('user@example.com', 'username');
}
```

## Troubleshooting

If you encounter issues:

1. Check that you've installed symfony/mailer: `composer require symfony/mailer`
2. Verify your Mailtrap credentials are correct
3. Check that your `.env` file has the correct MAILER_DSN format
4. Run `php bin/console debug:container mailer` to verify the mailer service is configured correctly 