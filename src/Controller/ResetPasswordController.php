<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DirectEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    private $entityManager;
    private $emailService;
    private $urlGenerator;

    public function __construct(
        EntityManagerInterface $entityManager, 
        DirectEmailService $emailService,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->emailService = $emailService;
        $this->urlGenerator = $urlGenerator;
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            // Find user by email
            $userRepository = $this->entityManager->getRepository(User::class);
            $user = $userRepository->findOneBy(['email' => $email]);
            
            if ($user) {
                // Generate a token and save it to the user
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $this->entityManager->flush();
                
                // Generate reset URL
                $resetUrl = $this->urlGenerator->generate(
                    'app_reset_password', 
                    ['token' => $token], 
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                
                // Send password reset email
                try {
                    $this->emailService->sendPasswordResetEmail(
                        $user->getEmail(),
                        $user->getUsername(),
                        $resetUrl
                    );
                    
                    $this->addFlash('success', 'Password reset instructions have been sent to your email.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'There was a problem sending the email. Please try again later.');
                }
            } else {
                // Don't reveal whether the email exists for security reasons
                $this->addFlash('success', 'If your email exists in our system, password reset instructions have been sent.');
            }
            
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('security/forgot_password.html.twig');
    }
    
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, string $token): Response
    {
        // Find user by token
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['reset_token' => $token]);
        
        if (!$user) {
            $this->addFlash('error', 'Invalid or expired password reset link.');
            return $this->redirectToRoute('app_login');
        }
        
        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            
            // Validate passwords
            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Passwords do not match.');
                return $this->render('security/reset_password.html.twig', [
                    'token' => $token
                ]);
            }
            
            // Update password and clear token
            $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
            $user->setResetToken(null);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Your password has been reset. You can now log in with your new password.');
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('security/reset_password.html.twig', [
            'token' => $token
        ]);
    }
} 