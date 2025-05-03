<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\EventRegistrationRepository;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/events')]
#[IsGranted('ROLE_ADMIN')]
class AdminEventRegistrationController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_event_registration_dashboard')]
    public function dashboard(EventRepository $eventRepository, EventRegistrationRepository $registrationRepository): Response
    {
        $events = $eventRepository->findAll();
        
        // Get current datetime
        $now = new \DateTime();
        
        // Filter upcoming events (events with eventDate >= now)
        $upcomingEvents = array_filter($events, function(Event $event) use ($now) {
            return $event->getEventDate() >= $now;
        });
        
        // Sort upcoming events by date (closest first)
        usort($upcomingEvents, function(Event $a, Event $b) {
            return $a->getEventDate() <=> $b->getEventDate();
        });
        
        // Calculate registration statistics
        $totalRegistrations = $registrationRepository->count([]);
        $paidRegistrations = $registrationRepository->countByPaymentStatus('paid');
        $pendingRegistrations = $registrationRepository->countByPaymentStatus('pending');
        
        return $this->render('admin/event_registration/dashboard.html.twig', [
            'events' => $events,
            'upcomingEvents' => $upcomingEvents,
            'totalRegistrations' => $totalRegistrations,
            'paidRegistrations' => $paidRegistrations,
            'pendingRegistrations' => $pendingRegistrations,
        ]);
    }
} 