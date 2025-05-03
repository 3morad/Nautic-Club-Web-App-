<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'payment_page')]
    #[IsGranted('ROLE_USER')]
    public function index(EventRepository $eventRepository): Response
    {
        // Get upcoming events for the booking page
        $upcomingEvents = $eventRepository->createQueryBuilder('e')
            ->where('e.eventDate >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
            
        return $this->render('payment/index.html.twig', [
            'upcoming_events' => $upcomingEvents,
        ]);
    }
    
    #[Route('/process/{id}', name: 'payment_process')]
    #[IsGranted('ROLE_USER')]
    public function process(int $id): Response
    {
        // Mock payment processing for event ID
        return $this->render('payment/process.html.twig', [
            'event_id' => $id,
        ]);
    }
    
    #[Route('/success', name: 'payment_success')]
    #[IsGranted('ROLE_USER')]
    public function success(): Response
    {
        // Payment success page
        return $this->render('payment/success.html.twig');
    }
    
    #[Route('/cancel', name: 'payment_cancel')]
    #[IsGranted('ROLE_USER')]
    public function cancel(): Response
    {
        // Payment cancellation page
        return $this->render('payment/cancel.html.twig');
    }
} 