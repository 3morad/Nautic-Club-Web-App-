<?php

namespace App\Controller;

use App\Entity\LocationWeather;
use App\Service\WeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/simple-location')]
class SimpleLocationController extends AbstractController
{
    private WeatherService $weatherService;
    
    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }
    
    #[Route('/new', name: 'simple_location_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $successMessage = null;
        $location = null;
        
        // Check if we're editing an existing location
        $editId = $request->query->get('edit');
        if ($editId) {
            $location = $entityManager->getRepository(LocationWeather::class)->find($editId);
            if (!$location) {
                $this->addFlash('info', 'Location not found.');
                return $this->redirectToRoute('simple_location_index');
            }
        }
        
        if ($request->isMethod('POST')) {
            if (!$location) {
                // Create new location
                $location = new LocationWeather();
            }
            
            // Update location from request parameters
            $location->setName($request->request->get('name'));
            $location->setRegion($request->request->get('region'));
            $location->setLatitude((float)$request->request->get('latitude'));
            $location->setLongitude((float)$request->request->get('longitude'));
            
            // Get weather data from the API
            try {
                $this->weatherService->updateLocationWeather($location);
                $weatherFetched = true;
            } catch (\Exception $e) {
                $weatherFetched = false;
                // Fallback to default values if API fails
                $location->setTemperature(20.0);
                $location->setWeatherCondition('Cloudy');
            }
            
            // Persist and flush
            $entityManager->persist($location);
            $entityManager->flush();
            
            $successMessage = $editId ? 'Location updated successfully!' : 'Location created successfully!';
            if (!$weatherFetched) {
                $successMessage .= ' (Weather data could not be fetched automatically)';
            }
            
            if ($editId) {
                // Redirect back to index after successful edit
                $this->addFlash('success', $successMessage);
                return $this->redirectToRoute('simple_location_index');
            }
        }
        
        return $this->render('simple_location/new.html.twig', [
            'success_message' => $successMessage,
            'location' => $location,
            'is_edit' => (bool)$editId,
        ]);
    }

    #[Route('/', name: 'simple_location_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $locations = $entityManager->getRepository(LocationWeather::class)->findAll();
        
        return $this->render('simple_location/index.html.twig', [
            'locations' => $locations,
        ]);
    }

    #[Route('/{id}', name: 'simple_location_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $location = $entityManager->getRepository(LocationWeather::class)->find($id);
        
        if (!$location) {
            throw $this->createNotFoundException('Location not found');
        }
        
        // Get events related to this location
        $events = $entityManager->getRepository('App\Entity\Event')->findBy(['locationWeather' => $location]);
        
        return $this->render('simple_location/show.html.twig', [
            'location' => $location,
            'events' => $events
        ]);
    }

    #[Route('/{id}/delete', name: 'simple_location_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $location = $entityManager->getRepository(LocationWeather::class)->find($id);
        
        if (!$location) {
            throw $this->createNotFoundException('Location not found');
        }
        
        // Check CSRF token
        if ($this->isCsrfTokenValid('delete'.$location->getId(), $request->request->get('_token'))) {
            // Check if location is used in events
            $events = $entityManager->getRepository('App\Entity\Event')->findBy(['locationWeather' => $location]);
            
            if (count($events) > 0) {
                $this->addFlash('info', 'Cannot delete location: it is used by ' . count($events) . ' event(s). Remove those events first.');
                return $this->redirectToRoute('simple_location_index');
            }
            
            $entityManager->remove($location);
            $entityManager->flush();
            
            $this->addFlash('success', 'Location deleted successfully');
        }
        
        return $this->redirectToRoute('simple_location_index');
    }

    #[Route('/{id}/refresh-weather', name: 'simple_location_refresh_weather', methods: ['POST'])]
    public function refreshWeather(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $location = $entityManager->getRepository(LocationWeather::class)->find($id);
        
        if (!$location) {
            throw $this->createNotFoundException('Location not found');
        }
        
        // Check CSRF token
        if ($this->isCsrfTokenValid('refresh'.$location->getId(), $request->request->get('_token'))) {
            try {
                $this->weatherService->updateLocationWeather($location);
                $entityManager->flush();
                $this->addFlash('success', 'Weather data successfully updated for ' . $location->getName());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Could not update weather data: ' . $e->getMessage());
            }
        }
        
        return $this->redirectToRoute('simple_location_index');
    }
} 