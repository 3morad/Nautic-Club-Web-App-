<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'user_profile')]
    public function profile(): Response
    {
        // Make sure the user is logged in
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'user_profile_edit')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Make sure the user is logged in
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        
        // Prevent changes to protected fields for non-admin users
        if (!$this->isGranted('ROLE_ADMIN') && $request->isMethod('POST')) {
            // If this is a form submission, ignore any attempts to change status or user_type
            $request->request->remove('status');
            $request->request->remove('user_type');
        }
        
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('user_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
} 