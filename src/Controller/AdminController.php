<?php

namespace App\Controller;

use App\Repository\TransactionRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Repository\FeedbackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/login', name: 'admin_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('admin/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(
        TransactionRepository $transactionRepository,
        TicketRepository $ticketRepository,
        UserRepository $userRepository,
        FeedbackRepository $feedbackRepository
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get total tickets
        $total_tickets = $ticketRepository->count([]);

        // Get total revenue from completed transactions
        $total_revenue = $transactionRepository->getTotalRevenue();

        // Get active users (users who have made at least one transaction)
        $active_users = $userRepository->countActiveUsers();

        // Get average rating from feedback
        $average_rating = $feedbackRepository->getAverageRating();

        // Get recent transactions
        $recent_transactions = $transactionRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            10
        );

        // Get revenue data for the last 6 months
        $revenue_data = $transactionRepository->getMonthlyRevenue();
        $revenue_labels = array_map(function($month) {
            return $month['month'];
        }, $revenue_data);
        $revenue_data = array_map(function($month) {
            return $month['revenue'];
        }, $revenue_data);

        // Get ticket sales data for the last 6 months
        $ticket_data = $ticketRepository->getMonthlyTicketSales();
        $ticket_labels = array_map(function($month) {
            return $month['month'];
        }, $ticket_data);
        $ticket_data = array_map(function($month) {
            return $month['tickets'];
        }, $ticket_data);

        return $this->render('admin/dashboard.html.twig', [
            'total_tickets' => $total_tickets,
            'total_revenue' => $total_revenue,
            'active_users' => $active_users,
            'average_rating' => $average_rating,
            'recent_transactions' => $recent_transactions,
            'revenue_labels' => $revenue_labels,
            'revenue_data' => $revenue_data,
            'ticket_labels' => $ticket_labels,
            'ticket_data' => $ticket_data,
        ]);
    }

    #[Route('/logout', name: 'admin_logout')]
    public function logout(): void
    {
        // This method can be empty - it will be intercepted by the logout key on your firewall
    }
} 