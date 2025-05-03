<?php

namespace App\EventRegistration\Service;

use App\Entity\EventRegistration;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class RegistrationWorkflowService
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private ?MailerInterface $mailer;
    private string $senderEmail;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PAID = 'paid';
    public const STATUS_WAITLISTED = 'waitlisted';

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ?MailerInterface $mailer = null,
        string $senderEmail = 'noreply@nautic-club.com'
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->senderEmail = $senderEmail;
    }

    /**
     * Updates the registration status and sends appropriate notifications
     */
    public function updateRegistrationStatus(EventRegistration $registration, string $newStatus): void
    {
        $oldStatus = $registration->getPaymentStatus();
        
        if ($oldStatus === $newStatus) {
            return;
        }

        // Update status
        $registration->setPaymentStatus($newStatus);
        $this->entityManager->flush();

        // Log status change
        $this->logger->info('Registration status changed', [
            'registration_id' => $registration->getId(),
            'event_id' => $registration->getEvent()->getId(),
            'user_id' => $registration->getUser()->getId(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);

        // Send notification
        $this->sendStatusChangeNotification($registration, $oldStatus, $newStatus);
    }

    /**
     * Sends email notification about status change
     */
    private function sendStatusChangeNotification(EventRegistration $registration, string $oldStatus, string $newStatus): void
    {
        if ($this->mailer === null) {
            $this->logger->warning('Mailer not configured, skipping notification');
            return;
        }

        $user = $registration->getUser();
        $event = $registration->getEvent();
        
        $subject = match($newStatus) {
            self::STATUS_CONFIRMED => 'Your registration has been confirmed',
            self::STATUS_CANCELLED => 'Your registration has been cancelled',
            self::STATUS_PAID => 'Payment received for your registration',
            self::STATUS_WAITLISTED => 'You have been added to the waitlist',
            default => 'Registration status update'
        };

        $message = match($newStatus) {
            self::STATUS_CONFIRMED => "Your registration for {$event->getName()} has been confirmed. We look forward to seeing you!",
            self::STATUS_CANCELLED => "Your registration for {$event->getName()} has been cancelled.",
            self::STATUS_PAID => "We've received your payment for {$event->getName()}. Your registration is now complete.",
            self::STATUS_WAITLISTED => "The event {$event->getName()} is currently full. You have been added to the waitlist and will be notified if a spot becomes available.",
            default => "The status of your registration for {$event->getName()} has changed from {$oldStatus} to {$newStatus}."
        };

        try {
            $email = (new Email())
                ->from($this->senderEmail)
                ->to($user->getEmail())
                ->subject($subject)
                ->text($message);

            $this->mailer->send($email);
            
            $this->logger->info('Status change notification sent', [
                'registration_id' => $registration->getId(),
                'user_email' => $user->getEmail(),
                'status' => $newStatus
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send status change notification', [
                'registration_id' => $registration->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process registration cancellation
     */
    public function cancelRegistration(EventRegistration $registration, ?string $reason = null): void
    {
        $this->updateRegistrationStatus($registration, self::STATUS_CANCELLED);
        
        if ($reason) {
            $registration->setNotes($registration->getNotes() . "\nCancellation reason: " . $reason);
            $this->entityManager->flush();
        }
        
        // Check if there are waitlisted registrations that can now be confirmed
        $this->processWaitlist($registration->getEvent());
    }

    /**
     * Process waitlist for an event
     */
    private function processWaitlist($event): void
    {
        // Get available spots
        $availableSpots = $event->getAvailableSpots();
        
        if ($availableSpots <= 0) {
            return;
        }
        
        // Find waitlisted registrations
        $waitlistedRegistrations = $this->entityManager->getRepository(EventRegistration::class)
            ->findBy(
                ['event' => $event, 'paymentStatus' => self::STATUS_WAITLISTED],
                ['createdAt' => 'ASC'] // Process oldest waitlisted registrations first
            );
            
        foreach ($waitlistedRegistrations as $waitlistedRegistration) {
            // If there are spots available and the quantity fits
            if ($availableSpots >= $waitlistedRegistration->getQuantity()) {
                $this->updateRegistrationStatus($waitlistedRegistration, self::STATUS_PENDING);
                $availableSpots -= $waitlistedRegistration->getQuantity();
                
                if ($availableSpots <= 0) {
                    break;
                }
            }
        }
    }
} 