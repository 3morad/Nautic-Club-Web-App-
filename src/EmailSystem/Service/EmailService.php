<?php

namespace App\EmailSystem\Service;

use App\Entity\User;
use App\Entity\EmailTemplate;
use App\Entity\EmailLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class EmailService
{
    private $mailer;
    private $twig;
    private $entityManager;
    private $senderEmail;
    private $senderName;
    private $logger;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        string $senderEmail = 'noreply@nautic-club.com',
        string $senderName = 'Nautic Club'
    ) {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->senderEmail = $senderEmail;
        $this->senderName = $senderName;
    }

    /**
     * Send an email using a template
     */
    public function sendTemplatedEmail(
        User $recipient, 
        string $templateCode, 
        array $context = [], 
        array $attachments = [],
        string $subject = null
    ): bool {
        try {
            // Get template from database or use a default one
            $template = $this->entityManager->getRepository(EmailTemplate::class)
                ->findOneBy(['code' => $templateCode]);
            
            if (!$template) {
                $this->logger->warning('Email template not found', ['template_code' => $templateCode]);
                $this->logFailedEmail($recipient, $templateCode, $subject ?? 'Template not found', 'Template not found');
                return false;
            }
            
            // Merge context with default variables
            $context = array_merge($context, [
                'recipient' => $recipient,
                'app_name' => 'Nautic Club',
                'current_year' => date('Y'),
            ]);
            
            // Render template
            try {
                $htmlContent = $this->twig->render(
                    '@EmailSystem/emails/' . $template->getViewName() . '.html.twig', 
                    $context
                );
            } catch (\Exception $e) {
                $this->logger->error('Failed to render email template', [
                    'template' => $templateCode,
                    'error' => $e->getMessage()
                ]);
                $this->logFailedEmail($recipient, $templateCode, $subject ?? $template->getSubject(), 'Template rendering error: ' . $e->getMessage());
                return false;
            }
            
            // Create email
            $email = (new Email())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($recipient->getEmail())
                ->subject($subject ?? $template->getSubject())
                ->html($htmlContent);
            
            // Add attachments if any
            foreach ($attachments as $attachment) {
                if (isset($attachment['content']) && isset($attachment['name'])) {
                    $email->attach($attachment['content'], $attachment['name']);
                }
            }
            
            // Send email
            $this->mailer->send($email);
            
            // Log the sent email
            $this->logEmail($recipient, $templateCode, $subject ?? $template->getSubject());
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send email', [
                'template' => $templateCode,
                'recipient' => $recipient->getEmail(),
                'error' => $e->getMessage()
            ]);
            
            $this->logFailedEmail($recipient, $templateCode, $subject ?? 'Failed Email', $e->getMessage());
            
            return false;
        }
    }
    
    /**
     * Send a direct email without a template
     */
    public function sendDirectEmail(
        string $to, 
        string $subject, 
        string $htmlContent, 
        array $attachments = []
    ): bool {
        try {
            $email = (new Email())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($to)
                ->subject($subject)
                ->html($htmlContent);
            
            // Add attachments if any
            foreach ($attachments as $attachment) {
                if (isset($attachment['content']) && isset($attachment['name'])) {
                    $email->attach($attachment['content'], $attachment['name']);
                }
            }
            
            $this->mailer->send($email);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to send direct email', [
                'recipient' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Log sent email for tracking
     */
    private function logEmail(User $recipient, string $templateCode, string $subject): void
    {
        try {
            $emailLog = new EmailLog();
            $emailLog->setUser($recipient);
            $emailLog->setEmailTemplate($templateCode);
            $emailLog->setSubject($subject);
            $emailLog->setSentAt(new \DateTime());
            $emailLog->setStatus('sent');
            
            $this->entityManager->persist($emailLog);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->warning('Failed to log email', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Log failed email
     */
    private function logFailedEmail(User $recipient, string $templateCode, string $subject, string $errorMessage): void
    {
        try {
            $emailLog = new EmailLog();
            $emailLog->setUser($recipient);
            $emailLog->setEmailTemplate($templateCode);
            $emailLog->setSubject($subject);
            $emailLog->setSentAt(new \DateTime());
            $emailLog->setStatus('failed');
            $emailLog->setErrorMessage($errorMessage);
            
            $this->entityManager->persist($emailLog);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->warning('Failed to log failed email', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Test an email template with a specific email address
     */
    public function sendTestEmail(
        string $templateCode, 
        string $recipientEmail, 
        string $recipientName = 'Test User',
        array $testData = []
    ): array {
        // Create temporary user for testing
        $user = new User();
        $user->setEmail($recipientEmail);
        $user->setFirstName($recipientName);
        $user->setLastName('Test');
        
        // Default test data context
        $context = array_merge([
            'testMode' => true,
            'testTimestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            // Add any test-specific variables needed for templates
            'eventName' => 'Test Event',
            'eventDate' => (new \DateTime('+1 week'))->format('Y-m-d H:i:s'),
            'orderNumber' => 'TEST-' . rand(10000, 99999),
            'amount' => '99.99',
        ], $testData);
        
        // Add test message to subject
        $subject = '[TEST] Email Template Preview - ' . $templateCode;
        
        try {
            $result = $this->sendTemplatedEmail($user, $templateCode, $context, [], $subject);
            
            if ($result) {
                return [
                    'success' => true, 
                    'message' => 'Test email successfully sent to ' . $recipientEmail
                ];
            } else {
                return [
                    'success' => false, 
                    'message' => 'Failed to send test email. Check logs for details.'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send an email to a recipient who may not have a User account
     * 
     * @param string $templateCode The template code to use
     * @param string $recipientEmail The recipient's email address
     * @param string $recipientName The recipient's name
     * @param array $context Additional context data for template variables
     * @param array $attachments Optional file attachments
     * @return array Result with success status and message
     */
    public function sendEmail(
        string $templateCode,
        string $recipientEmail,
        string $recipientName = '',
        array $context = [],
        array $attachments = []
    ): array {
        try {
            // Get template from database
            $template = $this->entityManager->getRepository(EmailTemplate::class)
                ->findOneBy(['code' => $templateCode]);
            
            if (!$template) {
                $this->logger->warning('Email template not found', ['template_code' => $templateCode]);
                return [
                    'success' => false,
                    'message' => 'Template not found: ' . $templateCode
                ];
            }
            
            // If recipient is a User object, use the sendTemplatedEmail method
            if (isset($context['user']) && $context['user'] instanceof User) {
                $result = $this->sendTemplatedEmail($context['user'], $templateCode, $context, $attachments);
                return [
                    'success' => $result,
                    'message' => $result ? 'Email sent successfully' : 'Failed to send email'
                ];
            }
            
            // Create a dummy user object for non-User recipients
            $tempUser = new User();
            $tempUser->setEmail($recipientEmail);
            
            // Set name if provided
            if (!empty($recipientName)) {
                $nameParts = explode(' ', $recipientName);
                $firstName = $nameParts[0];
                $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
                
                $tempUser->setFirstName($firstName);
                $tempUser->setLastName($lastName);
            } else {
                $tempUser->setFirstName('Guest');
                $tempUser->setLastName('User');
            }
            
            // Enhanced context with recipient info
            $enhancedContext = array_merge($context, [
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
            ]);
            
            // Send the email using the templated method
            $result = $this->sendTemplatedEmail($tempUser, $templateCode, $enhancedContext, $attachments);
            
            return [
                'success' => $result,
                'message' => $result ? 'Email sent successfully to ' . $recipientEmail : 'Failed to send email to ' . $recipientEmail
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error in sendEmail method', [
                'error' => $e->getMessage(),
                'templateCode' => $templateCode,
                'recipientEmail' => $recipientEmail
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send bulk emails to multiple recipients
     */
    public function sendBulkEmails(
        array $recipients,
        string $templateCode,
        array $context = [],
        array $attachments = []
    ): array {
        $results = [];
        
        foreach ($recipients as $recipient) {
            if (!$recipient instanceof User) {
                $this->logger->warning('Invalid recipient', ['recipient' => $recipient]);
                $results[] = [
                    'recipient' => 'Invalid',
                    'status' => false,
                    'error' => 'Recipient must be a User object'
                ];
                continue;
            }
            
            $success = $this->sendTemplatedEmail($recipient, $templateCode, $context, $attachments);
            
            $results[] = [
                'recipient' => $recipient->getEmail(),
                'status' => $success,
                'error' => $success ? null : 'Failed to send email'
            ];
        }
        
        return $results;
    }
} 