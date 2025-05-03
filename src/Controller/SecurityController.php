<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // If user is already logged in, redirect based on their role
        if ($this->getUser()) {
            // Get the target path if one is set in the request
            $targetPath = $request->get('_target_path');
            
            if (!$targetPath) {
                if (in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
                    return $this->redirectToRoute('admin_dashboard');
                }
                return $this->redirectToRoute('user_dashboard');
            }
            
            // If a target path is provided, redirect there
            return $this->redirect($targetPath);
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on your firewall
    }
    
    #[Route('/create-user', name: 'create_user')]
    public function createUser(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $successMessage = null;
        $errorMessage = null;
        
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');
            $password = $request->request->get('password');
            $isAdmin = $request->request->get('isAdmin') ? true : false;
            
            // Check if user already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            
            if ($existingUser) {
                $errorMessage = 'A user with this email already exists.';
            } else {
                $user = new User();
                $user->setEmail($email);
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                
                // Set roles
                $roles = ['ROLE_USER'];
                if ($isAdmin) {
                    $roles[] = 'ROLE_ADMIN';
                }
                $user->setRoles($roles);
                
                // Hash password
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
                
                // Save user
                $entityManager->persist($user);
                $entityManager->flush();
                
                $successMessage = 'User created successfully!';
            }
        }
        
        return $this->render('security/create_user.html.twig', [
            'success_message' => $successMessage,
            'error_message' => $errorMessage,
        ]);
    }
} 