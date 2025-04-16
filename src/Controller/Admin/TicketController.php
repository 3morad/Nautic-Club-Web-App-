<?php

namespace App\Controller\Admin;

use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tickets')]
#[IsGranted('ROLE_ADMIN')]
class TicketController extends AbstractController
{
    #[Route('/', name: 'admin_tickets_index', methods: ['GET'])]
    public function index(Request $request, TicketRepository $ticketRepository, PaginatorInterface $paginator): Response
    {
        $query = $ticketRepository->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery();

        $tickets = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/tickets/index.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/new', name: 'admin_tickets_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $ticket = new Ticket();
        
        // Set defaults to avoid null values
        $ticket->setCategory('general');
        $ticket->setStatus('open');
        $ticket->setPriority('medium');
        $ticket->setIsSupport(false); // Default to Event Ticket
        
        // Set defaults for event ticket fields
        $ticket->setTicketType('Regular Ticket');
        $ticket->setPrice(0);
        $ticket->setEventDate(new \DateTime('+1 week'));
        
        $form = $this->createForm(TicketType::class, $ticket);
        
        // Handle the dynamic_fields_loaded flag without using request->request directly
        if ($request->isMethod('POST') && $request->request->has('dynamic_fields_loaded')) {
            // Set isSupport value for proper form rendering
            if ($request->request->has('ticket') && isset($request->request->all('ticket')['isSupport'])) {
                $isSupport = $request->request->all('ticket')['isSupport'];
                $ticket->setIsSupport($isSupport === '1');
            }
            
            // Render the form with the updated ticket entity
            return $this->render('admin/tickets/form.html.twig', [
                'ticket' => $ticket,
                'form' => $form,
            ]);
        }
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Set common fields
            $ticket->setCreatedBy($this->getUser());
            $ticket->setCreatedAt(new \DateTimeImmutable());
            
            if ($ticket->isSupport()) {
                // Support ticket defaults
                $ticket->setTicketType('support');
                $ticket->setPrice(0);
                $ticket->setEventName('Support Ticket');
                $ticket->setEventDate(new \DateTime());
            } else {
                // Event ticket defaults if these fields are not set elsewhere in the form
                if (!$ticket->getTicketType()) {
                    $ticket->setTicketType('Regular Ticket');
                }
                if ($ticket->getPrice() === null) {
                    $ticket->setPrice(0);
                }
                if (!$ticket->getEventName()) {
                    $ticket->setEventName($ticket->getTitle());
                }
                if (!$ticket->getEventDate()) {
                    $ticket->setEventDate(new \DateTime('+1 week'));
                }
            }

            $entityManager->persist($ticket);
            $entityManager->flush();

            $this->addFlash('success', 'Ticket created successfully.');
            return $this->redirectToRoute('admin_tickets_index');
        }

        return $this->render('admin/tickets/form.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_tickets_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Ticket updated successfully.');
            return $this->redirectToRoute('admin_tickets_index');
        }

        return $this->render('admin/tickets/form.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_tickets_delete', methods: ['POST'])]
    public function delete(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ticket->getId(), $request->request->get('_token'))) {
            $entityManager->remove($ticket);
            $entityManager->flush();
            $this->addFlash('success', 'Ticket deleted successfully.');
        }

        return $this->redirectToRoute('admin_tickets_index');
    }
} 