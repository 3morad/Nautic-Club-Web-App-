<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\Transaction;
use App\Form\TicketType;
use App\Form\SupportTicketType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\QrCodeService;

#[Route('/ticket')]
class TicketController extends AbstractController
{
    private $qrCodeService;
    
    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }
    
    #[Route('/', name: 'ticket_index')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Create a new support ticket with appropriate defaults
        $newTicket = new Ticket();
        $newTicket->setIsSupport(true)
                  ->setStatus('open')
                  ->setPriority('high')
                  ->setTicketType('Support Ticket');
        
        // For event date and other potentially required fields
        $newTicket->setEventDate(new \DateTime('+2 weeks'))
                  ->setPrice(0.00)
                  ->setEventName('N/A');
        
        // Use our specialized support ticket form
        $form = $this->createForm(SupportTicketType::class, $newTicket);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Set the creation metadata
            $newTicket->setCreatedBy($this->getUser())
                      ->setCreatedAt(new \DateTimeImmutable());
    
            $entityManager->persist($newTicket);
            $entityManager->flush();
    
            $this->addFlash('success', 'Your support ticket has been submitted successfully and will be reviewed shortly.');
            
            // Redirect back to ticket list page
            return $this->redirectToRoute('ticket_index', ['type' => 'support']);
        }
    
        // 2) Decide which tickets to fetch based on ?type=
        $type = $request->query->get('type', 'support');        // default = "support"
        $repo = $entityManager->getRepository(Ticket::class);
    
        if ($type === 'support') {
            $tickets = $repo->findBy(
                ['isSupport' => true, 'status' => 'open'],
                ['createdAt' => 'DESC']
            );
        } else {
            $tickets = $repo->findBy(
                ['isSupport' => false],
                ['eventDate' => 'DESC']
            );
        }
        
    
        // 3) Single render at the end
        return $this->render('ticket/index.html.twig', [
            'tickets'     => $tickets,
            'form'        => $form->createView(),
            'currentType' => $type,
        ]);
    }
    

    #[Route('/{id}', name: 'ticket_show', methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        // Generate QR code only for event tickets, not support tickets
        $qrCodeDataUri = null;
        
        if (!$ticket->isSupport()) {
            // Generate ticket validation data
            $validationData = $this->qrCodeService->generateTicketValidationData(
                $ticket->getId(),
                $ticket->getEventName(),
                $ticket->getEventDate(),
                $ticket->getTicketType()
            );
            
            // Generate QR code data URI
            $qrCodeDataUri = $this->qrCodeService->generateQrCodeDataUri($validationData);
        }
        
        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
            'qr_code_data_uri' => $qrCodeDataUri
        ]);
    }

    #[Route('/{id}/download', name: 'ticket_download')]
    public function download(Ticket $ticket): Response
    {
        // Only allow downloading event tickets, not support tickets
        if ($ticket->isSupport()) {
            $this->addFlash('error', 'Support tickets cannot be downloaded');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        // Generate ticket validation data
        $validationData = $this->qrCodeService->generateTicketValidationData(
            $ticket->getId(),
            $ticket->getEventName(),
            $ticket->getEventDate(),
            $ticket->getTicketType()
        );
        
        // Generate QR code data URI
        $qrCodeDataUri = $this->qrCodeService->generateQrCodeDataUri($validationData);
        
        // Generate PDF ticket with QR code
        return $this->render('ticket/download.html.twig', [
            'ticket' => $ticket,
            'qr_code_data_uri' => $qrCodeDataUri
        ]);
    }

    #[Route('/{id}/validate', name: 'ticket_validate', methods: ['POST'])]
    public function validate(Ticket $ticket, Request $request): Response
    {
        $ticketData = $request->get('ticket_data');
        
        // Validate the ticket data
        $validatedData = $this->qrCodeService->validateTicketData($ticketData);
        
        if (!$validatedData) {
            return $this->json([
                'valid' => false,
                'message' => 'Invalid ticket data'
            ], 400);
        }
        
        // Verify that the ticket ID matches
        if ($validatedData['id'] != $ticket->getId()) {
            return $this->json([
                'valid' => false,
                'message' => 'Ticket ID mismatch'
            ], 400);
        }
        
        // Additional validation logic can be added here
        // For example, checking if the ticket has been used already
        
        return $this->json([
            'valid' => true,
            'message' => 'Ticket is valid',
            'ticket' => [
                'id' => $ticket->getId(),
                'type' => $ticket->getTicketType(),
                'event' => $ticket->getEventName(),
                'date' => $ticket->getEventDate()->format('Y-m-d H:i:s')
            ]
        ]);
    }
    
    #[Route('/{id}/scan', name: 'ticket_scan', methods: ['GET'])]
    public function scanTicket(Ticket $ticket): Response
    {
        // Only allow scanning event tickets, not support tickets
        if ($ticket->isSupport()) {
            $this->addFlash('error', 'Support tickets cannot be scanned');
            return $this->redirectToRoute('ticket_show', ['id' => $ticket->getId()]);
        }
        
        return $this->render('ticket/scan.html.twig', [
            'ticket' => $ticket
        ]);
    }
} 