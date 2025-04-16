<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'featured_events' => [
                [
                    'name' => 'Summer Beach Party',
                    'date' => '2025-07-15',
                    'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                    'description' => 'Join us for an unforgettable beach party with live music, food, and drinks.'
                ],
                [
                    'name' => 'Sailing Regatta',
                    'date' => '2025-08-20',
                    'image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                    'description' => 'Watch or participate in our annual sailing regatta with prizes for winners.'
                ],
                [
                    'name' => 'Sunset Cruise',
                    'date' => '2025-09-10',
                    'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80',
                    'description' => 'Enjoy a romantic sunset cruise with dinner and entertainment.'
                ]
            ]
        ]);
    }
} 