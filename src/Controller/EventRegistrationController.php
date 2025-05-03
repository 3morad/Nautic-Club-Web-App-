<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Repository\EventRegistrationRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/event-registration')]
#[IsGranted('ROLE_USER')]
class EventRegistrationController extends AbstractController
{
    #[Route('/form/{id}', name: 'event_registration_form', methods: ['GET'])]
    public function showRegistrationForm(int $id, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        // Check if user is already registered
        $existingRegistration = $entityManager->getRepository(EventRegistration::class)
            ->findOneByEventAndUser($event, $this->getUser());
            
        if ($existingRegistration) {
            $this->addFlash('info', 'You are already registered for this event.');
            return $this->redirectToRoute('simple_event_show', ['id' => $event->getId()]);
        }
        
        // Check if event is full
        if ($event->isFull()) {
            $this->addFlash('error', 'This event is already at full capacity.');
            return $this->redirectToRoute('simple_event_show', ['id' => $event->getId()]);
        }
        
        return $this->render('event_registration/form.html.twig', [
            'event' => $event
        ]);
    }

    #[Route('/register/{id}', name: 'event_registration_register', methods: ['POST'])]
    public function register(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        // Check if user is already registered
        $existingRegistration = $entityManager->getRepository(EventRegistration::class)
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
        
        // Check if event has enough capacity for requested quantity
        if ($event->getCapacity() !== null && $event->getRegistrationCount() + $quantity > $event->getCapacity()) {
            $this->addFlash('error', 'Not enough available spots for your requested quantity.');
            return $this->redirectToRoute('event_registration_form', ['id' => $event->getId()]);
        }
        
        // Create a new registration
        $registration = new EventRegistration();
        $registration->setEvent($event);
        $registration->setUser($this->getUser());
        $registration->setPaymentStatus('pending');
        $registration->setQuantity($quantity);
        $registration->setNotes($notes);
        
        $entityManager->persist($registration);
        $entityManager->flush();
        
        $this->addFlash('success', 'You have successfully registered for ' . $event->getName() . ' with ' . $quantity . ' tickets.');
        
        // If the event is free, mark as paid immediately
        if ($event->getPrice() <= 0) {
            $registration->setPaymentStatus('paid');
            $entityManager->flush();
            return $this->redirectToRoute('event_registration_my_registrations');
        }
        
        // Otherwise, redirect to payment
        return $this->redirectToRoute('event_registration_payment', ['id' => $registration->getId()]);
    }
    
    #[Route('/cancel/{id}', name: 'event_registration_cancel', methods: ['POST'])]
    public function cancel(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $registration = $entityManager->getRepository(EventRegistration::class)->find($id);
        
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
        
        $eventName = $registration->getEvent()->getName();
        
        $entityManager->remove($registration);
        $entityManager->flush();
        
        $this->addFlash('success', 'Your registration for ' . $eventName . ' has been cancelled.');
        
        return $this->redirectToRoute('event_registration_my_registrations');
    }
    
    #[Route('/my-registrations', name: 'event_registration_my_registrations')]
    public function myRegistrations(EventRegistrationRepository $registrationRepository): Response
    {
        $registrations = $registrationRepository->findByUser($this->getUser());
        
        return $this->render('event_registration/my_registrations.html.twig', [
            'registrations' => $registrations
        ]);
    }
    
    #[Route('/proceed-to-payment/{id}', name: 'event_registration_payment', methods: ['GET'])]
    public function proceedToPayment(int $id, EntityManagerInterface $entityManager): Response
    {
        $registration = $entityManager->getRepository(EventRegistration::class)->find($id);
        
        if (!$registration) {
            throw $this->createNotFoundException('Registration not found');
        }
        
        // Security check - only allow users to pay for their own registrations
        if ($registration->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot pay for someone else\'s registration');
        }
        
        // Here, you would normally redirect to an external payment processor
        // For now, we'll just render a mock payment page
        return $this->render('event_registration/payment.html.twig', [
            'registration' => $registration
        ]);
    }
    
    #[Route('/payment-success/{id}', name: 'event_registration_payment_success', methods: ['GET'])]
    public function paymentSuccess(int $id, EntityManagerInterface $entityManager): Response
    {
        $registration = $entityManager->getRepository(EventRegistration::class)->find($id);
        
        if (!$registration) {
            throw $this->createNotFoundException('Registration not found');
        }
        
        $registration->setPaymentStatus('paid');
        $entityManager->flush();
        
        $this->addFlash('success', 'Payment successful! You are now fully registered for ' . $registration->getEvent()->getName());
        
        return $this->redirectToRoute('event_registration_my_registrations');
    }
    
    #[Route('/admin/all', name: 'event_registration_admin_all')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminAllRegistrations(EventRegistrationRepository $registrationRepository): Response
    {
        $registrations = $registrationRepository->findAll();
        
        $stats = [
            'total' => count($registrations),
            'paid' => $registrationRepository->countByPaymentStatus('paid'),
            'pending' => $registrationRepository->countByPaymentStatus('pending')
        ];
        
        return $this->render('event_registration/admin_all.html.twig', [
            'registrations' => $registrations,
            'stats' => $stats
        ]);
    }
    
    #[Route('/admin/event/{id}', name: 'event_registration_admin_event')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminEventRegistrations(int $id, EventRepository $eventRepository, EventRegistrationRepository $registrationRepository): Response
    {
        $event = $eventRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $registrations = $registrationRepository->findByEvent($event);
        
        return $this->render('event_registration/admin_event.html.twig', [
            'event' => $event,
            'registrations' => $registrations
        ]);
    }
    
    #[Route('/admin/update-status/{id}', name: 'event_registration_admin_update_status', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminUpdateStatus(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $registration = $entityManager->getRepository(EventRegistration::class)->find($id);
        
        if (!$registration) {
            throw $this->createNotFoundException('Registration not found');
        }
        
        $status = $request->request->get('status');
        if (in_array($status, ['pending', 'paid', 'cancelled'])) {
            $registration->setPaymentStatus($status);
            $entityManager->flush();
            
            $this->addFlash('success', 'Registration status updated to ' . $status);
        }
        
        return $this->redirectToRoute('event_registration_admin_event', [
            'id' => $registration->getEvent()->getId()
        ]);
    }
} 