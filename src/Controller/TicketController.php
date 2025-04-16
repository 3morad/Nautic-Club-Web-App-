<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

#[Route('/ticket')]
class TicketController extends AbstractController
{
    #[Route('/', name: 'ticket_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $tickets = $entityManager->getRepository(Ticket::class)->findAll();

        return $this->render('ticket/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/{id}', name: 'ticket_show', methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}/download', name: 'ticket_download')]
    public function download(Ticket $ticket, BuilderInterface $qrBuilder): Response
    {
        // Generate ticket QR code
        $qrCode = $qrBuilder
            ->data(json_encode([
                'ticketId' => $ticket->getId(),
                'type' => $ticket->getTicketType(),
                'event' => $ticket->getEventName(),
                'date' => $ticket->getEventDate()->format('Y-m-d H:i:s')
            ]))
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->build();

        // Generate PDF ticket
        return $this->render('ticket/download.html.twig', [
            'ticket' => $ticket,
            'qrCode' => $qrCode->getDataUri()
        ]);
    }

    #[Route('/{id}/validate', name: 'ticket_validate', methods: ['POST'])]
    public function validate(Ticket $ticket, Request $request): Response
    {
        // Add validation logic here
        // This could check if the ticket is valid, not expired, and not already used
        
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
} 