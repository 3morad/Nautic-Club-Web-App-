<?php

namespace App\EventCalendar\Controller;

use App\Entity\Event;
use App\Entity\LocationWeather;
use App\EventCalendar\Service\CalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/event-calendar")
 */
class CalendarController extends AbstractController
{
    private $calendarService;
    private $entityManager;

    public function __construct(
        CalendarService $calendarService,
        EntityManagerInterface $entityManager
    ) {
        $this->calendarService = $calendarService;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="event_calendar_index")
     * @IsGranted("ROLE_USER")
     */
    public function index(Request $request): Response
    {
        // Get current year and month, default to current month
        $year = $request->query->getInt('year', (int)date('Y'));
        $month = $request->query->getInt('month', (int)date('n'));
        
        // Validate year and month
        if ($month < 1 || $month > 12) {
            $month = (int)date('n');
        }
        
        if ($year < 2020 || $year > 2030) {
            $year = (int)date('Y');
        }
        
        // Get filter parameters
        $filters = [
            'location' => $request->query->get('location'),
            'minPrice' => $request->query->get('min_price'),
            'maxPrice' => $request->query->get('max_price'),
            'hasAvailability' => $request->query->getBoolean('has_availability'),
            'searchTerm' => $request->query->get('search'),
        ];
        
        // Generate calendar data
        $calendarData = $this->calendarService->generateCalendarData($year, $month, $filters);
        
        // Get locations for filter
        $locations = $this->entityManager->getRepository(LocationWeather::class)->findAll();
        
        return $this->render('@EventCalendar/calendar/index.html.twig', [
            'calendarData' => $calendarData,
            'locations' => $locations,
            'filters' => $filters,
        ]);
    }

    /**
     * @Route("/day/{year}/{month}/{day}", name="event_calendar_day", requirements={"year"="\d+", "month"="\d+", "day"="\d+"})
     * @IsGranted("ROLE_USER")
     */
    public function day(int $year, int $month, int $day, Request $request): Response
    {
        // Get events for the selected day
        $events = $this->calendarService->getEventsForDay($year, $month, $day);
        
        $date = new \DateTime("$year-$month-$day");
        
        return $this->render('@EventCalendar/calendar/day.html.twig', [
            'events' => $events,
            'date' => $date,
            'year' => $year,
            'month' => $month,
            'day' => $day,
        ]);
    }

    /**
     * @Route("/event/{id}/export", name="event_calendar_export_event", requirements={"id"="\d+"})
     * @IsGranted("ROLE_USER")
     */
    public function exportEvent(int $id): Response
    {
        $event = $this->entityManager->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $icalContent = $this->calendarService->generateICalForEvent($event);
        
        $filename = 'event_' . $event->getId() . '_' . $this->slugify($event->getName()) . '.ics';
        
        $response = new Response($icalContent);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', $disposition);
        
        return $response;
    }

    /**
     * @Route("/event/{id}/add-to-google", name="event_calendar_add_to_google", requirements={"id"="\d+"})
     * @IsGranted("ROLE_USER")
     */
    public function addToGoogleCalendar(int $id): Response
    {
        $event = $this->entityManager->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        // Format dates for Google Calendar
        $startDate = $event->getEventDate()->format('Ymd\THis\Z');
        $endDate = clone $event->getEventDate();
        $endDate->modify('+2 hours');
        $endDateFormatted = $endDate->format('Ymd\THis\Z');
        
        $location = $event->getLocationWeather() ? 
            $event->getLocationWeather()->getName() . ', ' . $event->getLocationWeather()->getRegion() : 
            'Location TBD';
            
        $googleCalendarUrl = 'https://calendar.google.com/calendar/render';
        $googleCalendarUrl .= '?action=TEMPLATE';
        $googleCalendarUrl .= '&text=' . urlencode($event->getName());
        $googleCalendarUrl .= '&dates=' . urlencode($startDate) . '/' . urlencode($endDateFormatted);
        $googleCalendarUrl .= '&details=' . urlencode(strip_tags($event->getDescription()));
        $googleCalendarUrl .= '&location=' . urlencode($location);
        
        return $this->redirect($googleCalendarUrl);
    }

    /**
     * Helper function to convert a string to a slug
     */
    private function slugify(string $text): string
    {
        // Replace non letter or digit characters with dash
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
        // Trim dash from start and end
        $text = trim($text, '-');
        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // Lowercase
        $text = strtolower($text);
        // Remove unwanted characters
        $text = preg_replace('/[^-\w]+/', '', $text);
        
        if (empty($text)) {
            return 'n-a';
        }
        
        return $text;
    }
} 