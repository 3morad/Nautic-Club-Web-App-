<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/event')]
class EventController extends AbstractController
{
    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(): Response
    {
        // Redirect to simple event index
        return $this->redirectToRoute('simple_event_index');
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        // Redirect to simple event new
        return $this->redirectToRoute('simple_event_new');
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        // Redirect to simple event show
        return $this->redirectToRoute('simple_event_show', ['id' => $id]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        // Redirect to simple event edit
        return $this->redirectToRoute('simple_event_edit', ['id' => $id]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        // Forward to simple event delete
        return $this->forward(SimpleEventController::class . '::delete', [
            'id' => $id,
            'request' => $request
        ]);
    }

    #[Route('/nearby', name: 'app_event_nearby', methods: ['GET'])]
    public function nearby(Request $request, EventRepository $eventRepository): Response
    {
        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');
        $radius = $request->query->get('radius', 10); // Default 10km radius
        
        $events = [];
        
        if ($latitude && $longitude) {
            $events = $eventRepository->findEventsByProximity(
                (float) $latitude,
                (float) $longitude,
                (float) $radius
            );
        }
        
        return $this->render('event/nearby.html.twig', [
            'events' => $events,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius,
        ]);
    }
    
    #[Route('/weather/{condition}', name: 'app_event_by_weather', methods: ['GET'])]
    public function byWeather(string $condition, EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findUpcomingEventsByWeather($condition);
        
        return $this->render('event/by_weather.html.twig', [
            'events' => $events,
            'condition' => $condition,
        ]);
    }
} 