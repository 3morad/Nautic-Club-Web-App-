<?php

namespace App\Controller\Admin;

use App\Entity\Feedback;
use App\Form\AdminFeedbackType;
use App\Repository\FeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/feedback')]
#[IsGranted('ROLE_ADMIN')]
class FeedbackController extends AbstractController
{
    #[Route('/', name: 'admin_feedback_index', methods: ['GET'])]
    public function index(FeedbackRepository $feedbackRepository): Response
    {
        return $this->render('admin/feedback/index.html.twig', [
            'feedbacks' => $feedbackRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'admin_feedback_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $feedback = new Feedback();
        $feedback->setIsAdmin(true);
        
        $form = $this->createForm(AdminFeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $feedback->setCreatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($feedback);
            $entityManager->flush();

            $this->addFlash('success', 'New featured feedback created successfully.');
            return $this->redirectToRoute('admin_feedback_index');
        }

        return $this->render('admin/feedback/form.html.twig', [
            'feedback' => $feedback,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_feedback_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdminFeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Feedback updated successfully.');
            return $this->redirectToRoute('admin_feedback_index');
        }

        return $this->render('admin/feedback/form.html.twig', [
            'feedback' => $feedback,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_feedback_delete', methods: ['POST'])]
    public function delete(Request $request, Feedback $feedback, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$feedback->getId(), $request->request->get('_token'))) {
            $entityManager->remove($feedback);
            $entityManager->flush();
            $this->addFlash('success', 'Feedback deleted successfully.');
        }

        return $this->redirectToRoute('admin_feedback_index');
    }
} 