<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Entity\Transaction;
use App\Entity\User;
use App\Form\FeedbackType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\FormSpreeService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/feedback')]
class FeedbackController extends AbstractController
{
    private $formSpreeService;
    
    public function __construct(FormSpreeService $formSpreeService)
    {
        $this->formSpreeService = $formSpreeService;
    }

    /**
     * Display all feedback entries
     */
    #[Route('/', name: 'app_feedback_index', methods: ['GET'])]
    public function listFeedback(EntityManagerInterface $entityManager): Response
    {
        $feedbackRepository = $entityManager->getRepository(Feedback::class);
        
        $feedbacks = $feedbackRepository->createQueryBuilder('f')
            ->where('f.isAdmin = :isAdmin')
            ->setParameter('isAdmin', false)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
            
        // Group feedbacks by event name
        $feedbacksByEvent = [];
        foreach ($feedbacks as $feedback) {
            $eventName = $feedback->getEventName();
            if (!isset($feedbacksByEvent[$eventName])) {
                $feedbacksByEvent[$eventName] = [];
            }
            $feedbacksByEvent[$eventName][] = $feedback;
        }
        
        // Get the events with statistics
        $events = $this->getEventsList($entityManager);
        
        return $this->render('feedback/index.html.twig', [
            'feedbacks' => $feedbacks,
            'feedbacksByEvent' => $feedbacksByEvent,
            'averageRating' => $feedbackRepository->getAverageRating(),
            'events' => $events,
            'showForm' => true
        ]);
    }

    /**
     * Create a new feedback
     */
    #[Route('/new', name: 'app_feedback_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $feedback = new Feedback();
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // The user is now set through the form
            $feedback->setCreatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($feedback);
            $entityManager->flush();

            $this->addFlash('success', 'Thank you for your feedback!');
            return $this->redirectToRoute('app_feedback_index');
        }

        return $this->render('feedback/new.html.twig', [
            'feedback' => $feedback,
            'form' => $form,
        ]);
    }

    /**
     * Show a specific feedback
     */
    #[Route('/{id}', name: 'app_feedback_show', methods: ['GET'])]
    public function show(Feedback $feedback): Response
    {
        return $this->render('feedback/show.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    /**
     * Edit an existing feedback
     */
    #[Route('/{id}/edit', name: 'app_feedback_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Feedback updated successfully.');
            return $this->redirectToRoute('app_feedback_index');
        }

        return $this->render('feedback/edit.html.twig', [
            'feedback' => $feedback,
            'form' => $form,
        ]);
    }
    #[Route('/{id}', name: 'app_feedback_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$feedback->getId(), $request->request->get('_token'))) {
            $entityManager->remove($feedback);
            $entityManager->flush();
            $this->addFlash('success', 'Feedback deleted successfully.');
        }

        return $this->redirectToRoute('app_feedback_index');
    }

    /**
     * Show feedback related to a transaction
     */
    #[Route('/transaction/{transactionId}', name: 'feedback_page', requirements: ['transactionId' => '\d+'])]
    public function showTransactionFeedback(int $transactionId, EntityManagerInterface $entityManager): Response
    {
        $transaction = $entityManager->getRepository(Transaction::class)->find($transactionId);
        
        if (!$transaction) {
            throw $this->createNotFoundException('Transaction not found');
        }
        
        return $this->render('feedback/show.html.twig', [
            'transaction' => $transaction
        ]);
    }

    /**
     * Handle feedback submission via API
     */
    #[Route('/submit', name: 'feedback_submit', methods: ['POST'])]
    public function submitFeedback(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        
        $transaction = $entityManager->getRepository(Transaction::class)->find($data['transactionId']);
        
        if (!$transaction) {
            return $this->json(['error' => 'Transaction not found'], 404);
        }
        
        $feedback = new Feedback();
        $feedback->setRating($data['rating']);
        $feedback->setComment($data['comment']);
        $feedback->setUsername($data['username']);
        $feedback->setTransaction($transaction);
        
        // Set a default user (use transaction's user if available, otherwise use a default user ID 1)
        if ($transaction->getUser()) {
            $feedback->setUser($transaction->getUser());
        } else {
            $defaultUser = $entityManager->getRepository(User::class)->find(1);
            if (!$defaultUser) {
                return $this->json(['error' => 'Default user not found'], 500);
            }
            $feedback->setUser($defaultUser);
        }
        
        // Handle photo upload if present
        if (isset($data['photo'])) {
            // Implement photo upload logic here
            // $feedback->setPhotoPath($uploadedPhotoPath);
        }
        
        $entityManager->persist($feedback);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'feedbackId' => $feedback->getId()
        ]);
    }

    /**
     * Add placeholder feedback for demonstration purposes
     */
    #[Route('/add-placeholders', name: 'app_feedback_add_placeholders', methods: ['GET'])]
    public function addPlaceholderFeedback(EntityManagerInterface $entityManager): Response
    {
        // Sample event names
        $eventNames = [
            'Annual Regatta 2023',
            'Summer Sailing Camp',
            'Coastal Race Challenge',
            'Beginners Yacht Training',
            'Nautical Festival'
        ];
        
        // Sample usernames
        $usernames = [
            'JohnSailor', 
            'MarineLover', 
            'OceanExplorer', 
            'SailingQueen', 
            'CaptainJack',
            'WaveRider',
            'SeaAdventurer'
        ];
        
        // Sample comments
        $comments = [
            'Amazing experience! The instructors were very knowledgeable and friendly.',
            'Had a wonderful time at this event. Will definitely come back next year!',
            'The organization was top-notch. Everything went smoothly.',
            'I learned so much during this event. The staff was very professional.',
            'Great atmosphere and beautiful location. Highly recommended!',
            'My family enjoyed every moment. The children especially loved the water activities.',
            'A perfect day on the water with excellent guidance from the team.',
            'The equipment was in excellent condition and the safety measures were impressive.',
            'Fantastic event for both beginners and experienced sailors alike.',
            'The views were breathtaking and the experience unforgettable!'
        ];
        
        // Create 10 random feedback entries
        for ($i = 0; $i < 10; $i++) {
            $feedback = new Feedback();
            $feedback->setRating(random_int(3, 5));
            $feedback->setUsername($usernames[array_rand($usernames)]);
            $feedback->setComment($comments[array_rand($comments)]);
            $feedback->setEventName($eventNames[array_rand($eventNames)]);
            $feedback->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', random_int(1, 60))));
            $feedback->setIsAdmin(false);
            
            $entityManager->persist($feedback);
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', '10 placeholder feedback entries have been added successfully!');
        return $this->redirectToRoute('app_feedback_index');
    }

    #[Route('/form', name: 'app_feedback_form', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        return $this->render('feedback/index.html.twig', [
            'events' => $this->getEventsList($entityManager)
        ]);
    }
    
    #[Route('/form/submit', name: 'app_feedback_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $data = [
            'name' => $request->request->get('name'),
            'email' => $request->request->get('email'),
            'event' => $request->request->get('event'),
            'type' => $request->request->get('type', 'general'),
            'rating' => (int) $request->request->get('rating', 0),
            'message' => $request->request->get('message'),
            'submitted_from' => $request->getHost() . $request->getRequestUri(),
        ];
        
        // Submit feedback through FormSpree service
        $result = $this->formSpreeService->submitFeedback($data);
        
        // If successful, store in database too
        if ($result['success']) {
            try {
                $feedback = new Feedback();
                $feedback->setUsername($data['name']);
                $feedback->setComment($data['message']);
                $feedback->setRating($data['rating']);
                $feedback->setEventName($data['event']);
                $feedback->setCreatedAt(new \DateTimeImmutable());
                $feedback->setIsAdmin(false);
                
                // Set user if logged in
                if ($this->getUser()) {
                    $feedback->setUser($this->getUser());
                }
                
                $entityManager->persist($feedback);
                $entityManager->flush();
            } catch (\Exception $e) {
                // Log the error but continue with the thank you page
                // since FormSpree submission was successful
            }
            
            $this->addFlash('success', $result['message']);
            return $this->redirectToRoute('app_feedback_thank_you');
        } else {
            $this->addFlash('error', $result['message']);
            return $this->render('feedback/index.html.twig', [
                'error' => $result['message'],
                'data' => $data,
                'events' => $this->getEventsList($entityManager)
            ]);
        }
    }
    
    /**
     * Helper method to get events list
     */
    private function getEventsList(EntityManagerInterface $entityManager): array
    {
        $feedbackRepository = $entityManager->getRepository(Feedback::class);
        
        // Try to get events from repository
        $events = $feedbackRepository->getEventsWithStats();
        
        // If no events found yet, add some defaults
        if (empty($events)) {
            $events = [
                ['name' => 'Annual Regatta 2023', 'rating' => 4.8, 'count' => 24],
                ['name' => 'Summer Sailing Camp', 'rating' => 4.5, 'count' => 18],
                ['name' => 'Coastal Race Challenge', 'rating' => 4.9, 'count' => 15],
                ['name' => 'Beginners Yacht Training', 'rating' => 4.3, 'count' => 12],
                ['name' => 'Nautical Festival', 'rating' => 4.7, 'count' => 20]
            ];
        }
        
        return $events;
    }
    
    #[Route('/form/thank-you', name: 'app_feedback_thank_you', methods: ['GET'])]
    public function thankYou(): Response
    {
        return $this->render('feedback/thank_you.html.twig');
    }
    
    #[Route('/api/feedback', name: 'api_feedback_submit', methods: ['POST'])]
    public function apiSubmit(Request $request): JsonResponse
    {
        // Get JSON data from request
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON data'
            ], 400);
        }
        
        // Submit feedback through FormSpree service
        $result = $this->formSpreeService->submitFeedback($data);
        
        // Return JSON response with appropriate status code
        return $this->json($result, $result['success'] ? 200 : 400);
    }

    #[Route('/event/{eventName}', name: 'app_feedback_by_event', methods: ['GET'])]
    public function viewEventFeedback(string $eventName, FeedbackRepository $feedbackRepository): Response
    {
        // URL decode the event name
        $decodedEventName = urldecode($eventName);
        
        // Get feedback for this specific event
        $feedbacks = $feedbackRepository->createQueryBuilder('f')
            ->where('f.eventName = :eventName')
            ->andWhere('f.isAdmin = :isAdmin')
            ->setParameter('eventName', $decodedEventName)
            ->setParameter('isAdmin', false)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
            
        // Calculate average rating for this event
        $totalRating = 0;
        $count = 0;
        
        foreach ($feedbacks as $feedback) {
            $totalRating += $feedback->getRating();
            $count++;
        }
        
        $averageRating = $count > 0 ? round($totalRating / $count, 1) : 0;
        
        return $this->render('feedback/event.html.twig', [
            'event' => $decodedEventName,
            'feedbacks' => $feedbacks,
            'averageRating' => $averageRating,
            'count' => $count
        ]);
    }
    
    #[Route('/leave-feedback/event/{eventName}', name: 'app_leave_feedback_for_event', methods: ['GET'])]
    public function leaveFeedbackForEvent(string $eventName): Response
    {
        // URL decode the event name
        $decodedEventName = urldecode($eventName);
        
        return $this->render('feedback/index.html.twig', [
            'data' => [
                'event' => $decodedEventName
            ]
        ]);
    }
}