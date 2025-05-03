<?php

namespace App\EventRegistration\Controller;

use App\Entity\Event;
use App\Entity\EventRegistration;
use App\Entity\User;
use App\EventRegistration\Service\RegistrationWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/event-registration')]
#[IsGranted('ROLE_ADMIN')]
class AdminRegistrationController extends AbstractController
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

    #[Route('/dashboard', name: 'admin_registration_dashboard')]
    public function dashboard(): Response
    {
        $registrationRepository = $this->entityManager->getRepository(EventRegistration::class);
        
        // Statistics
        $stats = [
            'total' => $registrationRepository->count([]),
            'pending' => $registrationRepository->count(['paymentStatus' => RegistrationWorkflowService::STATUS_PENDING]),
            'confirmed' => $registrationRepository->count(['paymentStatus' => RegistrationWorkflowService::STATUS_CONFIRMED]),
            'paid' => $registrationRepository->count(['paymentStatus' => RegistrationWorkflowService::STATUS_PAID]),
            'waitlisted' => $registrationRepository->count(['paymentStatus' => RegistrationWorkflowService::STATUS_WAITLISTED]),
            'cancelled' => $registrationRepository->count(['paymentStatus' => RegistrationWorkflowService::STATUS_CANCELLED]),
        ];
        
        // Recent registrations
        $recentRegistrations = $registrationRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            10
        );
        
        // Events with most registrations
        $eventRepository = $this->entityManager->getRepository(Event::class);
        $events = $eventRepository->findUpcomingEvents(10);
        
        return $this->render('@EventRegistration/admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentRegistrations' => $recentRegistrations,
            'events' => $events,
            'statuses' => [
                RegistrationWorkflowService::STATUS_PENDING => 'Pending',
                RegistrationWorkflowService::STATUS_CONFIRMED => 'Confirmed',
                RegistrationWorkflowService::STATUS_PAID => 'Paid',
                RegistrationWorkflowService::STATUS_WAITLISTED => 'Waitlisted',
                RegistrationWorkflowService::STATUS_CANCELLED => 'Cancelled',
            ]
        ]);
    }
    
    #[Route('/all', name: 'admin_registration_all')]
    public function allRegistrations(Request $request): Response
    {
        $filter = $request->query->get('filter', '');
        $status = $request->query->get('status', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;
        
        $registrationRepository = $this->entityManager->getRepository(EventRegistration::class);
        
        // Build criteria based on filters
        $criteria = [];
        if ($status !== '') {
            $criteria['paymentStatus'] = $status;
        }
        
        // Count total
        $total = $registrationRepository->count($criteria);
        $pages = ceil($total / $limit);
        
        // Get current page results
        $registrations = $registrationRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );
        
        // If text filter is provided, filter results in PHP (simple implementation)
        if ($filter !== '') {
            $filteredRegistrations = [];
            foreach ($registrations as $registration) {
                $matchesFilter = 
                    str_contains(strtolower($registration->getUser()->getEmail()), strtolower($filter)) ||
                    str_contains(strtolower($registration->getUser()->getFullName()), strtolower($filter)) ||
                    str_contains(strtolower($registration->getEvent()->getName()), strtolower($filter)) ||
                    str_contains(strtolower((string)$registration->getId()), strtolower($filter));
                
                if ($matchesFilter) {
                    $filteredRegistrations[] = $registration;
                }
            }
            $registrations = $filteredRegistrations;
        }
        
        return $this->render('@EventRegistration/admin/all_registrations.html.twig', [
            'registrations' => $registrations,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'filter' => $filter,
            'status' => $status,
            'statuses' => [
                RegistrationWorkflowService::STATUS_PENDING => 'Pending',
                RegistrationWorkflowService::STATUS_CONFIRMED => 'Confirmed',
                RegistrationWorkflowService::STATUS_PAID => 'Paid',
                RegistrationWorkflowService::STATUS_WAITLISTED => 'Waitlisted',
                RegistrationWorkflowService::STATUS_CANCELLED => 'Cancelled',
            ]
        ]);
    }
    
    #[Route('/event/{id}', name: 'admin_registration_event')]
    public function eventRegistrations(int $id): Response
    {
        $event = $this->entityManager->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $registrations = $this->entityManager->getRepository(EventRegistration::class)
            ->findBy(['event' => $event], ['createdAt' => 'DESC']);
        
        // Calculate statistics
        $total = count($registrations);
        $totalConfirmed = 0;
        $totalPaid = 0;
        $totalPending = 0;
        $totalWaitlisted = 0;
        $totalCancelled = 0;
        
        foreach ($registrations as $registration) {
            switch ($registration->getPaymentStatus()) {
                case RegistrationWorkflowService::STATUS_CONFIRMED:
                    $totalConfirmed++;
                    break;
                case RegistrationWorkflowService::STATUS_PAID:
                    $totalPaid++;
                    break;
                case RegistrationWorkflowService::STATUS_PENDING:
                    $totalPending++;
                    break;
                case RegistrationWorkflowService::STATUS_WAITLISTED:
                    $totalWaitlisted++;
                    break;
                case RegistrationWorkflowService::STATUS_CANCELLED:
                    $totalCancelled++;
                    break;
            }
        }
        
        return $this->render('@EventRegistration/admin/event_registrations.html.twig', [
            'event' => $event,
            'registrations' => $registrations,
            'stats' => [
                'total' => $total,
                'confirmed' => $totalConfirmed,
                'paid' => $totalPaid,
                'pending' => $totalPending,
                'waitlisted' => $totalWaitlisted,
                'cancelled' => $totalCancelled,
            ],
            'statuses' => [
                RegistrationWorkflowService::STATUS_PENDING => 'Pending',
                RegistrationWorkflowService::STATUS_CONFIRMED => 'Confirmed',
                RegistrationWorkflowService::STATUS_PAID => 'Paid',
                RegistrationWorkflowService::STATUS_WAITLISTED => 'Waitlisted',
                RegistrationWorkflowService::STATUS_CANCELLED => 'Cancelled',
            ]
        ]);
    }
    
    #[Route('/user/{id}', name: 'admin_registration_user')]
    public function userRegistrations(int $id): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        
        $registrations = $this->entityManager->getRepository(EventRegistration::class)
            ->findBy(['user' => $user], ['createdAt' => 'DESC']);
        
        return $this->render('@EventRegistration/admin/user_registrations.html.twig', [
            'user' => $user,
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
    
    #[Route('/update-status/{id}', name: 'admin_registration_update_status', methods: ['POST'])]
    public function updateStatus(int $id, Request $request): Response
    {
        $registration = $this->entityManager->getRepository(EventRegistration::class)->find($id);
        
        if (!$registration) {
            throw $this->createNotFoundException('Registration not found');
        }
        
        $status = $request->request->get('status');
        $notes = $request->request->get('admin_notes');
        
        if (in_array($status, [
            RegistrationWorkflowService::STATUS_PENDING,
            RegistrationWorkflowService::STATUS_CONFIRMED,
            RegistrationWorkflowService::STATUS_PAID,
            RegistrationWorkflowService::STATUS_WAITLISTED,
            RegistrationWorkflowService::STATUS_CANCELLED,
        ])) {
            $this->registrationWorkflow->updateRegistrationStatus($registration, $status);
            
            if ($notes) {
                $registration->setNotes(($registration->getNotes() ? $registration->getNotes() . "\n\n" : '') . 
                    "Admin note [" . (new \DateTime())->format('Y-m-d H:i') . "]: " . $notes);
                $this->entityManager->flush();
            }
            
            $this->addFlash('success', 'Registration status updated to ' . $status);
        }
        
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('admin_registration_all'));
    }
} 