<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\LocationWeatherRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(EventRepository $eventRepository): Response
    {
        // Get upcoming events for the homepage
        $upcomingEvents = $eventRepository->createQueryBuilder('e')
            ->where('e.eventDate >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.eventDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        return $this->render('home/index.html.twig', [
            'upcoming_events' => $upcomingEvents,
        ]);
    }
    
    #[Route('/admin-dashboard', name: 'admin_dashboard')]
    public function adminDashboard(EventRepository $eventRepository, LocationWeatherRepository $locationRepository): Response
    {
        // You can add admin authorization check here
        
        // Get upcoming events
        $upcomingEvents = $eventRepository->createQueryBuilder('e')
            ->where('e.eventDate >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.eventDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
            
        // Get locations for map
        $locations = $locationRepository->findAll();
        
        // Count locations and calculate average temperature
        $locationsCount = count($locations);
        $totalTemp = 0;
        $weatherConditions = [];
        
        foreach ($locations as $location) {
            if ($location->getTemperature()) {
                $totalTemp += $location->getTemperature();
            }
            
            if ($location->getWeatherCondition()) {
                $condition = strtolower($location->getWeatherCondition());
                if (!isset($weatherConditions[$condition])) {
                    $weatherConditions[$condition] = 0;
                }
                $weatherConditions[$condition]++;
            }
        }
        
        $avgTemperature = $locationsCount > 0 ? round($totalTemp / $locationsCount, 1) : null;
        
        // Find most common weather condition
        $mostCommonCondition = !empty($weatherConditions) ? array_search(max($weatherConditions), $weatherConditions) : null;
        if ($mostCommonCondition) {
            $mostCommonCondition = ucfirst($mostCommonCondition);
        }
        
        // Mock data for charts
        $revenueLabelData = $this->getMockRevenueData();
        $ticketLabelData = $this->getMockTicketData();
        
        // Mock data for stats cards
        $totalTickets = 235;
        $totalRevenue = 12450.75;
        $activeUsers = 87;
        $averageRating = 4.6;
        
        // Mock data for recent transactions
        $recentTransactions = $this->getMockTransactions();
        
        return $this->render('admin/dashboard.html.twig', [
            'upcoming_events' => $upcomingEvents,
            'locations' => $locations,
            'locations_count' => $locationsCount,
            'avg_temperature' => $avgTemperature,
            'most_common_condition' => $mostCommonCondition,
            'revenue_labels' => $revenueLabelData['labels'],
            'revenue_data' => $revenueLabelData['data'],
            'ticket_labels' => $ticketLabelData['labels'],
            'ticket_data' => $ticketLabelData['data'],
            'total_tickets' => $totalTickets,
            'total_revenue' => $totalRevenue,
            'active_users' => $activeUsers,
            'average_rating' => $averageRating,
            'recent_transactions' => $recentTransactions
        ]);
    }
    
    private function getMockRevenueData(): array
    {
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [1500, 2500, 2100, 3200, 4800, 3800]
        ];
    }
    
    private function getMockTicketData(): array
    {
        return [
            'labels' => ['Sailing', 'Yacht Tours', 'Boat Rental', 'Wind Surfing', 'Special Events'],
            'data' => [45, 60, 75, 40, 25]
        ];
    }
    
    private function getMockTransactions(): array
    {
        $transactions = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $status = ['completed', 'pending', 'failed'][rand(0, $i > 3 ? 2 : 1)];
            $date = new \DateTime();
            $date->modify('-' . rand(1, 14) . ' days');
            
            $transactions[] = [
                'id' => rand(1000, 9999),
                'user' => [
                    'email' => 'user' . rand(1, 99) . '@example.com'
                ],
                'amount' => rand(50, 500) + (rand(0, 99) / 100),
                'status' => $status,
                'createdAt' => $date
            ];
        }
        
        return $transactions;
    }
    
    #[Route('/payment', name: 'payment_page')]
    public function payment(): Response
    {
        return $this->render('payment/index.html.twig');
    }
    
    #[Route('/tickets', name: 'ticket_index')]
    public function tickets(): Response
    {
        return $this->render('ticket/index.html.twig');
    }
    
    #[Route('/feedback', name: 'app_feedback_index')]
    public function feedback(): Response
    {
        return $this->render('feedback/index.html.twig');
    }
} 