<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\EmailTemplate;
use App\Entity\EmailLog;
use App\Entity\User;
use App\EmailSystem\Service\EmailService;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\EmailLogRepository;

#[Route('/email')]
class EmailDashboardController extends AbstractController
{
    private $entityManager;
    private $emailService;

    public function __construct(
        EntityManagerInterface $entityManager, 
        EmailService $emailService
    ) {
        $this->entityManager = $entityManager;
        $this->emailService = $emailService;
    }

    #[Route('/dashboard', name: 'email_dashboard')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get email template count
        $templateCount = $this->entityManager->getRepository(EmailTemplate::class)->count([]);

        // Get template categories
        $templates = $this->entityManager->getRepository(EmailTemplate::class)->findAll();
        $categories = [];
        foreach ($templates as $template) {
            if (!isset($categories[$template->getCategory()])) {
                $categories[$template->getCategory()] = 0;
            }
            $categories[$template->getCategory()]++;
        }

        // Get email log count
        $logCount = $this->entityManager->getRepository(EmailLog::class)->count([]);

        // Get log stats for the last 30 days
        $logRepo = $this->entityManager->getRepository(EmailLog::class);
        if (method_exists($logRepo, 'getStatsByPeriod')) {
            $startDate = new \DateTime('-30 days');
            $endDate = new \DateTime();
            $stats = $logRepo->getStatsByPeriod($startDate, $endDate);
        } else {
            $stats = [
                'sent' => 0,
                'failed' => 0,
                'total' => 0,
                'last24h' => 0,
                'successRate' => 100,
            ];
        }

        return $this->render('email_dashboard/index.html.twig', [
            'templateCount' => $templateCount,
            'templates' => $templates,
            'categories' => $categories,
            'logCount' => $logCount,
            'stats' => $stats,
        ]);
    }

    #[Route('/dashboard/templates.json', name: 'email_templates_json')]
    public function getTemplatesJson(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $templates = $this->entityManager->getRepository(EmailTemplate::class)->findAll();
        $result = [];
        
        foreach ($templates as $template) {
            $result[] = [
                'id' => $template->getId(),
                'code' => $template->getCode(),
                'name' => $template->getName(),
                'subject' => $template->getSubject(),
                'category' => $template->getCategory(),
            ];
        }
        
        return new JsonResponse($result);
    }

    #[Route('/dashboard/test', name: 'email_send_test', methods: ['POST'])]
    public function sendTestEmail(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $templateCode = $request->request->get('template');
        $recipientEmail = $request->request->get('recipient');
        $recipientName = $request->request->get('name', 'Test User');
        
        if (!$templateCode || !$recipientEmail) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Template code and recipient email are required'
            ], 400);
        }
        
        // Validate email
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid email address'
            ], 400);
        }
        
        // Check if template exists
        $template = $this->entityManager->getRepository(EmailTemplate::class)->findOneBy(['code' => $templateCode]);
        if (!$template) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        }
        
        // Send test email
        $result = $this->emailService->sendTestEmail(
            $templateCode,
            $recipientEmail,
            $recipientName
        );
        
        return new JsonResponse($result);
    }

    #[Route('/dashboard/bulk', name: 'email_bulk_send', methods: ['GET', 'POST'])]
    public function bulkSend(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($request->isMethod('POST')) {
            $templateCode = $request->request->get('template');
            $recipientType = $request->request->get('recipient_type');
            $customRecipients = $request->request->get('custom_recipients');
            
            // Validate inputs
            if (!$templateCode) {
                $this->addFlash('error', 'Please select a template');
                return $this->redirectToRoute('email_bulk_send');
            }
            
            // Get recipients based on selected type
            $recipients = [];
            if ($recipientType === 'all_users') {
                $users = $this->entityManager->getRepository(User::class)->findAll();
                foreach ($users as $user) {
                    $recipients[] = [
                        'email' => $user->getEmail(),
                        'name' => $user->getFirstName() . ' ' . $user->getLastName(),
                        'user' => $user
                    ];
                }
            } elseif ($recipientType === 'custom' && $customRecipients) {
                $emails = explode(',', $customRecipients);
                foreach ($emails as $email) {
                    $email = trim($email);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = [
                            'email' => $email,
                            'name' => '',
                            'user' => null
                        ];
                    }
                }
            }
            
            if (empty($recipients)) {
                $this->addFlash('error', 'No valid recipients found');
                return $this->redirectToRoute('email_bulk_send');
            }
            
            // Send emails
            $successCount = 0;
            $failCount = 0;
            
            foreach ($recipients as $recipient) {
                $result = $this->emailService->sendEmail(
                    $templateCode,
                    $recipient['email'],
                    $recipient['name'],
                    ['user' => $recipient['user']]
                );
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
            
            $this->addFlash('success', sprintf('Email campaign completed: %d sent successfully, %d failed', $successCount, $failCount));
            return $this->redirectToRoute('email_dashboard');
        }
        
        // Get templates for the form
        $templates = $this->entityManager->getRepository(EmailTemplate::class)->findAll();
        
        return $this->render('email_dashboard/bulk_send.html.twig', [
            'templates' => $templates
        ]);
    }
    
    #[Route('/dashboard/stats.json', name: 'email_stats_json')]
    public function getStatsJson(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get email log stats for the last 30 days
        $logRepo = $this->entityManager->getRepository(EmailLog::class);
        
        // Daily stats for the last 7 days
        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTime("-$i days");
            $startDate = clone $date;
            $startDate->setTime(0, 0, 0);
            $endDate = clone $date;
            $endDate->setTime(23, 59, 59);
            
            $sent = $logRepo->count([
                'status' => 'sent',
                'sentAt' => ['$gte' => $startDate, '$lte' => $endDate]
            ]);
            
            $failed = $logRepo->count([
                'status' => 'failed',
                'sentAt' => ['$gte' => $startDate, '$lte' => $endDate]
            ]);
            
            $dailyStats[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('D'),
                'sent' => $sent,
                'failed' => $failed
            ];
        }
        
        // Top templates
        $topTemplates = $logRepo->getTopTemplates();
        
        return new JsonResponse([
            'dailyStats' => $dailyStats,
            'topTemplates' => $topTemplates
        ]);
    }
}
