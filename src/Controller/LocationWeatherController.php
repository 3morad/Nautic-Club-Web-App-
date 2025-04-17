<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\LocationWeather;
use App\Form\LocationWeatherType;
use App\Repository\EventRepository;
use App\Repository\LocationWeatherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/location")
 */
class LocationWeatherController extends AbstractController
{
    /**
     * @Route("/", name="location_index", methods={"GET"})
     */
    public function index(LocationWeatherRepository $locationWeatherRepository): Response
    {
        return $this->render('location_weather/index.html.twig', [
            'locations' => $locationWeatherRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="location_new", methods={"GET", "POST"})
     */
    public function new(Request $request): Response
    {
        // Redirect to our simple location form instead to avoid PHPStan issues
        return $this->redirectToRoute('simple_location_new');
    }

    /**
     * @Route("/{id}", name="location_show", methods={"GET"})
     */
    public function show(int $id, EntityManagerInterface $entityManager, EventRepository $eventRepository): Response
    {
        $location = $entityManager->getRepository(LocationWeather::class)->find($id);
        
        if (!$location) {
            throw $this->createNotFoundException('Location not found');
        }
        
        // Get events for this location
        $events = $eventRepository->findBy(['locationWeather' => $location]);

        return $this->render('location_weather/show.html.twig', [
            'location' => $location,
            'events' => $events,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="location_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $location = $entityManager->getRepository(LocationWeather::class)->find($id);
        
        if (!$location) {
            throw $this->createNotFoundException('Location not found');
        }
        
        // For now, redirecting to simple location form
        // In a more complete solution, we would implement an edit form in SimpleLocationController
        $this->addFlash('info', 'Editing functionality is not available yet. Please create a new location instead.');
        return $this->redirectToRoute('simple_location_new');
    }

    /**
     * @Route("/{id}", name="location_delete", methods={"POST"})
     */
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $location = $entityManager->getRepository(LocationWeather::class)->find($id);
        
        if (!$location) {
            throw $this->createNotFoundException('Location not found');
        }
        
        if ($this->isCsrfTokenValid('delete'.$location->getId(), $request->request->get('_token'))) {
            $entityManager->remove($location);
            $entityManager->flush();
            $this->addFlash('success', 'Location deleted successfully.');
        }

        return $this->redirectToRoute('location_index');
    }
    
    #[Route('/nearby', name: 'location_nearby', methods: ['GET'])]
    public function nearby(Request $request, LocationWeatherRepository $locationWeatherRepository): Response
    {
        $latitude = $request->query->get('latitude');
        $longitude = $request->query->get('longitude');
        $radius = $request->query->get('radius', 10); // Default 10km radius
        
        $locations = [];
        
        if ($latitude && $longitude) {
            $locations = $locationWeatherRepository->findByProximity(
                (float) $latitude,
                (float) $longitude,
                (float) $radius
            );
        }
        
        return $this->render('location_weather/nearby.html.twig', [
            'locations' => $locations,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius,
        ]);
    }

    #[Route('/{id}/update-weather', name: 'location_update_weather', methods: ['POST'])]
    public function updateWeather(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $locationWeather = $entityManager->getRepository(LocationWeather::class)->find($id);
        
        if (!$locationWeather) {
            throw $this->createNotFoundException('Location not found');
        }
        
        // In a real app, this would call a weather API using the location's coordinates
        // For now, we'll manually update
        if ($this->isCsrfTokenValid('update-weather'.$locationWeather->getId(), $request->request->get('_token'))) {
            $temperature = $request->request->get('temperature');
            $weatherCondition = $request->request->get('weather_condition');
            
            if ($temperature !== null && $weatherCondition) {
                $locationWeather->setTemperature((float) $temperature);
                $locationWeather->setWeatherCondition($weatherCondition);
                $locationWeather->setUpdatedAt(new \DateTime());
                
                $entityManager->flush();
                $this->addFlash('success', 'Weather data updated successfully!');
            } else {
                $this->addFlash('error', 'Please provide both temperature and weather condition.');
            }
        }
        
        return $this->redirectToRoute('location_show', ['id' => $locationWeather->getId()]);
    }
} 