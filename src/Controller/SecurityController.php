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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\String\ByteString;
use Symfony\Component\HttpFoundation\RedirectResponse;
use DateTime;
use App\EmailSystem\Service\EmailService;

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

    #[Route('/forgot-password', name: 'forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, EntityManagerInterface $entityManager, EmailService $emailService): Response
    {
        $successMessage = null;
        $errorMessage = null;
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $token = ByteString::fromRandom(32)->toString();
                $user->setResetPasswordToken($token);
                $user->setResetTokenExpiresAt((new DateTime())->modify('+1 hour'));
                $entityManager->flush();
                // Send reset email
                $resetUrl = $this->generateUrl('reset_password', ['token' => $token], 0);
                $emailService->sendDirectEmail(
                    $user->getEmail(),
                    'Password Reset Request',
                    '<p>To reset your password, click <a href="' . $resetUrl . '">here</a>.<br>This link will expire in 1 hour.</p>'
                );
                $successMessage = 'If your email exists in our system, you will receive a password reset link.';
            } else {
                $successMessage = 'If your email exists in our system, you will receive a password reset link.';
            }
        }
        return $this->render('security/forgot_password.html.twig', [
            'success_message' => $successMessage,
            'error_message' => $errorMessage,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'reset_password', methods: ['GET', 'POST'])]
    public function resetPassword($token, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['resetPasswordToken' => $token]);
        $errorMessage = null;
        $successMessage = null;
        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new DateTime()) {
            $errorMessage = 'Invalid or expired reset token.';
            return $this->render('security/reset_password.html.twig', [
                'error_message' => $errorMessage,
                'success_message' => $successMessage,
                'token' => $token
            ]);
        }
        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            if (strlen($password) < 8) {
                $errorMessage = 'Password must be at least 8 characters.';
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setResetPasswordToken(null);
                $user->setResetTokenExpiresAt(null);
                $entityManager->flush();
                $successMessage = 'Your password has been reset. You can now log in.';
                return $this->redirectToRoute('app_login');
            }
        }
        return $this->render('security/reset_password.html.twig', [
            'error_message' => $errorMessage,
            'success_message' => $successMessage,
            'token' => $token
        ]);
    }
} 