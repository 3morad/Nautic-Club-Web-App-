<?php

namespace App\EventRegistration\Controller;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\EventRegistration\Service\RegistrationWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/event-registration')]
#[IsGranted('ROLE_USER')]
class RegistrationController extends AbstractController
{
    private $registrationWorkflow;
    private $entityManager;

    public function __construct(
        RegistrationWorkflowService $registrationWorkflow,
        EntityManagerInterface $entityManager
    ) {
        $this->registrationWorkflow = $registrationWorkflow;
        $this->entityManager = $entityManager;
    }

    #[Route('/form/{id}', name: 'event_registration_form', methods: ['GET'])]
    public function showRegistrationForm(int $id): Response
    {
        $event = $this->entityManager->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        // Check if user is already registered
        $existingRegistration = $this->entityManager->getRepository(EventRegistration::class)
            ->findOneByEventAndUser($event, $this->getUser());
            
        if ($existingRegistration) {
            $this->addFlash('info', 'You are already registered for this event.');
            return $this->redirectToRoute('simple_event_show', ['id' => $event->getId()]);
        }
        
        // Check if event is full
        if ($event->isFull()) {
            $this->addFlash('info', 'This event is currently at capacity. You can join the waitlist.');
            // Show special waitlist form view
            return $this->render('@EventRegistration/registration/waitlist_form.html.twig', [
                'event' => $event
            ]);
        }
        
        return $this->render('@EventRegistration/registration/form.html.twig', [
            'event' => $event
        ]);
    }

    #[Route('/register/{id}', name: 'event_registration_register', methods: ['POST'])]
    public function register(int $id, Request $request): Response
    {
        $event = $this->entityManager->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        // Check if user is already registered
        $existingRegistration = $this->entityManager->getRepository(EventRegistration::class)
            ->findOneByEventAndUser($event, $this->getUser());
            
        if ($existingRegistration) {
            $this->addFlash('info', 'You are already registered for this event.');
            return $this->redirectToRoute('simple_event_show', ['id' => $event->getId()]);
        }
        
        // Get form data
        $quantity = (int) $request->request->get('quantity', 1);
        $notes = $request->request->get('notes');
        
        // Validate quantity
        if ($quantity < 1) {
            $quantity = 1;
        } elseif ($quantity > 10) {
            $quantity = 10;
        }
        
        // Create a new registration
        $registration = new EventRegistration();
        $registration->setEvent($event);
        $registration->setUser($this->getUser());
        $registration->setQuantity($quantity);
        $registration->setNotes($notes);
        
        // Determine initial status based on event capacity
        if ($event->isFull() || ($event->getCapacity() !== null && $event->getRegistrationCount() + $quantity > $event->getCapacity())) {
            // Add to waitlist
            $registration->setPaymentStatus(RegistrationWorkflowService::STATUS_WAITLISTED);
            $this->entityManager->persist($registration);
            $this->entityManager->flush();
            
            $this->addFlash('info', 'The event is at capacity. You have been added to the waitlist and will be notified if a spot becomes available.');
            return $this->redirectToRoute('event_registration_my_registrations');
        }
        
        // Regular registration process
        $registration->setPaymentStatus(RegistrationWorkflowService::STATUS_PENDING);
        $this->entityManager->persist($registration);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'You have successfully registered for ' . $event->getName() . ' with ' . $quantity . ' tickets.');
        
        // If the event is free, mark as confirmed immediately
        if ($event->getPrice() <= 0) {
            $this->registrationWorkflow->updateRegistrationStatus(
                $registration, 
                RegistrationWorkflowService::STATUS_CONFIRMED
            );
            return $this->redirectToRoute('event_registration_my_registrations');
        }
        
        // Otherwise, redirect to payment
        return $this->redirectToRoute('event_registration_payment', ['id' => $registration->getId()]);
    }
    
    #[Route('/cancel/{id}', name: 'event_registration_cancel', methods: ['POST'])]
    public function cancel(int $id, Request $request): Response
    {
        $registration = $this->entityManager->getRepository(EventRegistration::class)->find($id);
        
        if (!$registration) {
            throw $this->createNotFoundException('Registration not found');
        }
        
        // Security check - only allow users to cancel their own registrations
        if ($registration->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You cannot cancel someone else\'s registration');
        }
        
        // Check if CSRF token is valid
        if (!$this->isCsrfTokenValid('cancel'.$registration->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
        
        $reason = $request->request->get('reason');
        $this->registrationWorkflow->cancelRegistration($registration, $reason);
        
        $this->addFlash('success', 'Your registration for ' . $registration->getEvent()->getName() . ' has been cancelled.');
        
        return $this->redirectToRoute('event_registration_my_registrations');
    }
    
    #[Route('/my-registrations', name: 'event_registration_my_registrations')]
    public function myRegistrations(): Response
    {
        $registrations = $this->entityManager->getRepository(EventRegistration::class)
            ->findByUser($this->getUser());
        
        return $this->render('@EventRegistration/registration/my_registrations.html.twig', [
            'registrations' => $registrations,
            'statuses' => [
                RegistrationWorkflowService::STATUS_PENDING => 'Pending',
                RegistrationWorkflowService::STATUS_CONFIRMED => 'Confirmed',
                RegistrationWorkflowService::STATUS_PAID => 'Paid',
                RegistrationWorkflowService::STATUS_WAITLISTED => 'Waitlisted',
                RegistrationWorkflowService::STATUS_CANCELLED => 'Cancelled',
            ]
        ]);
    }
    
    #[Route('/proceed-to-payment/{id}', name: 'event_registration_payment', methods: ['GET'])]
    public function proceedToPayment(int $id): Response
    {
        $registration = $this->entityManager->getRepository(EventRegistration::class)->find($id);
        
        if (!$registration) {
            throw $this->createNotFoundException('Registration not found');
        }
        
        // Security check - only allow users to pay for their own registrations
        if ($registration->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot pay for someone else\'s registration');
        }
        
        // Here, you would normally redirect to an external payment processor
        // For now, we'll just render a mock payment page
        return $this->render('@EventRegistration/registration/payment.html.twig', [
            'registration' => $registration
        ]);
    }
    
    #[Route('/payment-success/{id}', name: 'event_registration_payment_success', methods: ['GET'])]
    public function paymentSuccess(int $id): Response
    {
        $registration = $this->entityManager->getRepository(EventRegistration::class)->find($id);
        
        if (!$registration) {
            throw $this->createNotFoundException('Registration not found');
        }
        
        // Security check
        if ($registration->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
        
        $this->registrationWorkflow->updateRegistrationStatus(
            $registration, 
            RegistrationWorkflowService::STATUS_PAID
        );
        
        $this->addFlash('success', 'Payment successful! You are now fully registered for ' . $registration->getEvent()->getName());
        
        return $this->redirectToRoute('event_registration_my_registrations');
    }
} 