<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\LocationWeatherRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    
    #[Route('/user-dashboard', name: 'user_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function userDashboard(EventRepository $eventRepository): Response
    {
        // Get upcoming events for the user
        $upcomingEvents = $eventRepository->createQueryBuilder('e')
            ->where('e.eventDate >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.eventDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
            
        return $this->render('dashboard/user.html.twig', [
            'upcoming_events' => $upcomingEvents,
            'user' => $this->getUser(),
        ]);
    }
    
    #[Route('/admin-dashboard', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDashboard(EventRepository $eventRepository, LocationWeatherRepository $locationRepository): Response
    {
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
        
        return $this->render('admin/dashboard.html.twig', [
            'upcoming_events' => $upcomingEvents,
            'locations' => $locations,
            'locations_count' => $locationsCount,
            'avg_temperature' => $avgTemperature,
            'most_common_condition' => $mostCommonCondition,
            'revenue_labels' => $this->getMockRevenueData()['labels'],
            'revenue_data' => $this->getMockRevenueData()['data'],
            'ticket_labels' => $this->getMockTicketData()['labels'],
            'ticket_data' => $this->getMockTicketData()['data'],
            'total_tickets' => 235,
            'total_revenue' => 12450.75,
            'active_users' => 87,
            'average_rating' => 4.6,
            'recent_transactions' => $this->getMockTransactions()
        ]);
    }
    
    #[Route('/tickets', name: 'ticket_index')]
    #[IsGranted('ROLE_USER')]
    public function tickets(): Response
    {
        return $this->render('ticket/index.html.twig');
    }
    
    #[Route('/feedback', name: 'app_feedback_index')]
    #[IsGranted('ROLE_USER')]
    public function feedback(): Response
    {
        return $this->render('feedback/index.html.twig');
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
} 