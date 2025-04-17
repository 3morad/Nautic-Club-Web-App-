<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/transactions', name: 'admin_transactions_index')]
    public function transactionIndex(): Response
    {
        // Mock transactions data for the demo
        $transactions = [];
        
        for ($i = 1; $i <= 15; $i++) {
            $status = ['completed', 'pending', 'failed'][rand(0, $i > 12 ? 2 : 1)];
            $date = new \DateTime();
            $date->modify('-' . rand(1, 30) . ' days');
            
            $transactions[] = [
                'id' => rand(1000, 9999),
                'user' => [
                    'email' => 'user' . rand(1, 99) . '@example.com'
                ],
                'amount' => rand(50, 500) + (rand(0, 99) / 100),
                'status' => $status,
                'createdAt' => $date,
                'description' => ['Yacht Tour Package', 'Annual Membership', 'Boat Rental', 'Sailing Lessons', 'Event Ticket'][rand(0, 4)]
            ];
        }
        
        return $this->render('admin/transactions.html.twig', [
            'transactions' => $transactions
        ]);
    }
    
    #[Route('/tickets', name: 'admin_tickets_index')]
    public function ticketIndex(): Response
    {
        // Mock tickets data for the demo
        $tickets = [];
        
        $statuses = ['available', 'sold', 'reserved', 'cancelled'];
        $types = ['Standard', 'VIP', 'Group', 'Family', 'Special'];
        
        for ($i = 1; $i <= 20; $i++) {
            $tickets[] = [
                'id' => rand(1000, 9999),
                'event' => 'Event #' . rand(100, 999),
                'type' => $types[rand(0, 4)],
                'price' => rand(20, 150) + (rand(0, 99) / 100),
                'status' => $statuses[rand(0, 3)],
                'purchaseDate' => (rand(0, 3) > 0) ? new \DateTime('-' . rand(1, 30) . ' days') : null
            ];
        }
        
        return $this->render('admin/tickets.html.twig', [
            'tickets' => $tickets
        ]);
    }
    
    #[Route('/feedback', name: 'admin_feedback_index')]
    public function feedbackIndex(): Response
    {
        // Mock feedback data for the demo
        $feedback = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $date = new \DateTime();
            $date->modify('-' . rand(1, 60) . ' days');
            
            $feedback[] = [
                'id' => rand(1000, 9999),
                'user' => 'User ' . rand(1, 99),
                'rating' => rand(1, 5),
                'comment' => 'This is sample feedback #' . $i . '. ' . (rand(0, 1) ? 'Great service!' : 'Could be improved.'),
                'createdAt' => $date,
                'isResolved' => rand(0, 1)
            ];
        }
        
        return $this->render('admin/feedback.html.twig', [
            'feedback' => $feedback
        ]);
    }
} 