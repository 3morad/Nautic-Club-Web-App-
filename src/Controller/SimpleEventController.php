<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\LocationWeather;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/simple-event')]
class SimpleEventController extends AbstractController
{
    #[Route('/', name: 'simple_event_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $events = $entityManager->getRepository(Event::class)->findAll();
        
        return $this->render('simple_event/index.html.twig', [
            'events' => $events,
        ]);
    }
    
    #[Route('/new', name: 'simple_event_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $successMessage = null;
        $locations = $entityManager->getRepository(LocationWeather::class)->findAll();
        
        if ($request->isMethod('POST')) {
            // Create new event from request parameters
            $event = new Event();
            $event->setName($request->request->get('name'));
            $event->setDescription($request->request->get('description'));
            
            // Parse date
            $eventDate = new \DateTime($request->request->get('date'));
            $event->setEventDate($eventDate);
            
            // Find and set location
            $locationId = $request->request->get('location_id');
            $location = $entityManager->getRepository(LocationWeather::class)->find($locationId);
            if ($location) {
                $event->setLocationWeather($location);
            }
            
            // Handle image upload
            $imageFile = $request->files->get('event_image');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/events',
                        $newFilename
                    );
                    
                    $event->setImageFilename($newFilename);
                } catch (\Exception $e) {
                    // Error handling
                    $this->addFlash('error', 'There was a problem uploading your image.');
                }
            }
            
            // Persist and flush
            $entityManager->persist($event);
            $entityManager->flush();
            
            $successMessage = 'Event created successfully!';
        }
        
        return $this->render('simple_event/new.html.twig', [
            'success_message' => $successMessage,
            'locations' => $locations,
        ]);
    }
    
    #[Route('/{id}', name: 'simple_event_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        return $this->render('simple_event/show.html.twig', [
            'event' => $event,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'simple_event_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $event = $entityManager->getRepository(Event::class)->find($id);
        $locations = $entityManager->getRepository(LocationWeather::class)->findAll();
        $successMessage = null;
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        if ($request->isMethod('POST')) {
            // Update event from request parameters
            $event->setName($request->request->get('name'));
            $event->setDescription($request->request->get('description'));
            
            // Parse date
            $eventDate = new \DateTime($request->request->get('date'));
            $event->setEventDate($eventDate);
            
            // Find and set location
            $locationId = $request->request->get('location');
            $location = $entityManager->getRepository(LocationWeather::class)->find($locationId);
            if ($location) {
                $event->setLocationWeather($location);
            }
            
            // Handle image upload
            $imageFile = $request->files->get('event_image');
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/events',
                        $newFilename
                    );
                    
                    // Remove old image if exists
                    $oldFilename = $event->getImageFilename();
                    if ($oldFilename) {
                        $oldFilePath = $this->getParameter('kernel.project_dir').'/public/uploads/events/'.$oldFilename;
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                    
                    $event->setImageFilename($newFilename);
                } catch (\Exception $e) {
                    // Error handling
                    $this->addFlash('error', 'There was a problem uploading your image.');
                }
            }
            
            // Flush changes
            $entityManager->flush();
            
            $successMessage = 'Event updated successfully!';
        }
        
        return $this->render('simple_event/edit.html.twig', [
            'event' => $event,
            'locations' => $locations,
            'success_message' => $successMessage,
        ]);
    }
    
    #[Route('/{id}', name: 'simple_event_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        // Check CSRF token
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            // Remove image if exists
            $filename = $event->getImageFilename();
            if ($filename) {
                $filePath = $this->getParameter('kernel.project_dir').'/public/uploads/events/'.$filename;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $entityManager->remove($event);
            $entityManager->flush();
            
            $this->addFlash('success', 'Event deleted successfully');
        }
        
        return $this->redirectToRoute('simple_event_index');
    }
} 