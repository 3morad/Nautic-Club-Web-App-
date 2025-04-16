<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Entity\Transaction;
use App\Form\FeedbackType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/feedback')]
class FeedbackController extends AbstractController
{
    /**
     * Display all feedback entries
     */
    #[Route('/', name: 'app_feedback_index', methods: ['GET'])]
    public function listFeedback(FeedbackRepository $feedbackRepository): Response
    {
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
        
        return $this->render('feedback/index.html.twig', [
            'feedbacks' => $feedbacks,
            'feedbacksByEvent' => $feedbacksByEvent,
            'averageRating' => $feedbackRepository->getAverageRating(),
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
            $feedback->setUser($this->getUser());
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
}