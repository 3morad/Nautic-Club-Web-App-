<?php

namespace App\EmailSystem\Controller;

use App\Entity\EmailLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/email-logs')]
class EmailLogController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'email_log_index')]
    public function index(Request $request): Response
    {
        // Check if user has admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;

        $logs = $this->entityManager->getRepository(EmailLog::class)->findRecent($page, $limit);
        $totalLogs = $this->entityManager->getRepository(EmailLog::class)->countTotal();
        $totalPages = ceil($totalLogs / $limit);

        // Get stats for the last 30 days
        $startDate = new \DateTime('-30 days');
        $endDate = new \DateTime();
        $stats = $this->entityManager->getRepository(EmailLog::class)->getStatsByPeriod($startDate, $endDate);

        return $this->render('@EmailSystem/logs/index.html.twig', [
            'logs' => $logs,
            'page' => $page,
            'totalPages' => $totalPages,
            'stats' => $stats,
        ]);
    }

    #[Route('/user/{id}', name: 'email_log_user')]
    public function userLogs(int $id): Response
    {
        // Check if user has admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $logs = $this->entityManager->getRepository(EmailLog::class)->findByUser($user, 50);

        return $this->render('@EmailSystem/logs/user.html.twig', [
            'user' => $user,
            'logs' => $logs,
        ]);
    }

    #[Route('/template/{code}', name: 'email_log_template')]
    public function templateLogs(string $code): Response
    {
        // Check if user has admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $logs = $this->entityManager->getRepository(EmailLog::class)->findByTemplate($code);
        $template = $this->entityManager->getRepository('App:EmailTemplate')->findOneBy(['code' => $code]);

        if (!$template) {
            $this->addFlash('warning', 'Template not found, but showing logs anyway.');
        }

        return $this->render('@EmailSystem/logs/template.html.twig', [
            'template' => $template,
            'logs' => $logs,
            'templateCode' => $code,
        ]);
    }

    #[Route('/stats', name: 'email_log_stats')]
    public function stats(Request $request): Response
    {
        // Check if user has admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $period = $request->query->get('period', '30days');

        // Calculate date range based on period
        $endDate = new \DateTime();
        
        switch ($period) {
            case '7days':
                $startDate = new \DateTime('-7 days');
                break;
            case '30days':
                $startDate = new \DateTime('-30 days');
                break;
            case 'year':
                $startDate = new \DateTime('-1 year');
                break;
            default:
                $startDate = new \DateTime('-30 days');
        }

        $stats = $this->entityManager->getRepository(EmailLog::class)->getStatsByPeriod($startDate, $endDate);

        // Get stats by template
        $templateStats = $this->entityManager->createQueryBuilder()
            ->select('t.code as template_code, t.name as template_name, COUNT(l.id) as count')
            ->from('App:EmailLog', 'l')
            ->leftJoin('App:EmailTemplate', 't', 'WITH', 'l.emailTemplate = t.code')
            ->where('l.sentAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('t.code, t.name')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('@EmailSystem/logs/stats.html.twig', [
            'stats' => $stats,
            'templateStats' => $templateStats,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
} 