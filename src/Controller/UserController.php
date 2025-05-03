<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/user/profile', name: 'user_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/user/profile/edit', name: 'user_edit_profile')]
    #[IsGranted('ROLE_USER')]
    public function editProfile(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to edit your profile.');
        }

        // Create a form from UserType but remove status/user_type/password
        $form = $this->createForm(UserType::class, $user, [
            'is_registration' => false,
        ]);

        // Add password manually (unmapped)
        $form->add('password', PasswordType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'New Password',
            'attr' => ['autocomplete' => 'new-password'],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();

            if ($newPassword) {
                $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
            }

            $em->flush();

            $this->addFlash('success', 'Your profile has been updated.');
            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/edit_profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
