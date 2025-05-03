<?php

namespace App\EventCalendar\Service;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;

class CalendarService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get events for a specific month
     */
    public function getEventsForMonth(int $year, int $month): array
    {
        $startDate = new \DateTime("$year-$month-01 00:00:00");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        $endDate->setTime(23, 59, 59);
        
        return $this->getEventsBetweenDates($startDate, $endDate);
    }
    
    /**
     * Get events for a specific day
     */
    public function getEventsForDay(int $year, int $month, int $day): array
    {
        $startDate = new \DateTime("$year-$month-$day 00:00:00");
        $endDate = new \DateTime("$year-$month-$day 23:59:59");
        
        return $this->getEventsBetweenDates($startDate, $endDate);
    }
    
    /**
     * Get events between two dates
     */
    public function getEventsBetweenDates(\DateTimeInterface $startDate, \DateTimeInterface $endDate, array $filters = []): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('e')
            ->from(Event::class, 'e')
            ->where('e.eventDate >= :startDate')
            ->andWhere('e.eventDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.eventDate', 'ASC');
            
        // Apply location filter
        if (isset($filters['location']) && $filters['location']) {
            $qb->andWhere('e.locationWeather = :location')
                ->setParameter('location', $filters['location']);
        }
        
        // Apply price filter
        if (isset($filters['minPrice']) && is_numeric($filters['minPrice'])) {
            $qb->andWhere('e.price >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice']);
        }
        
        if (isset($filters['maxPrice']) && is_numeric($filters['maxPrice'])) {
            $qb->andWhere('e.price <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }
        
        // Apply availability filter
        if (isset($filters['hasAvailability']) && $filters['hasAvailability']) {
            $qb->andWhere('e.capacity > (
                SELECT COALESCE(SUM(er.quantity), 0) 
                FROM App\Entity\EventRegistration er 
                WHERE er.event = e.id AND er.paymentStatus != :cancelledStatus
            )')
            ->setParameter('cancelledStatus', 'cancelled');
        }
        
        // Apply search term
        if (isset($filters['searchTerm']) && !empty($filters['searchTerm'])) {
            $qb->andWhere('e.name LIKE :searchTerm OR e.description LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $filters['searchTerm'] . '%');
        }
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Generate calendar data for a month
     */
    public function generateCalendarData(int $year, int $month, array $filters = []): array
    {
        // Get the first day of the month
        $firstDayOfMonth = new \DateTime("$year-$month-01");
        $daysInMonth = (int)$firstDayOfMonth->format('t');
        $startDayOfWeek = (int)$firstDayOfMonth->format('N'); // 1 (Monday) to 7 (Sunday)
        
        // Get events for this month
        $events = $this->getEventsForMonth($year, $month);
        
        // Create a map of day => events
        $eventsMap = [];
        foreach ($events as $event) {
            $day = (int)$event->getEventDate()->format('j');
            if (!isset($eventsMap[$day])) {
                $eventsMap[$day] = [];
            }
            $eventsMap[$day][] = $event;
        }
        
        // Generate calendar weeks
        $calendar = [];
        $dayCount = 1;
        $weekCount = 0;
        
        // First week with potential empty days
        $calendar[$weekCount] = array_fill(0, 7, null);
        for ($i = $startDayOfWeek - 1; $i < 7 && $dayCount <= $daysInMonth; $i++) {
            $calendar[$weekCount][$i] = [
                'day' => $dayCount,
                'events' => $eventsMap[$dayCount] ?? [],
                'isToday' => $this->isToday($year, $month, $dayCount),
            ];
            $dayCount++;
        }
        $weekCount++;
        
        // Middle weeks
        while ($dayCount <= $daysInMonth) {
            $calendar[$weekCount] = array_fill(0, 7, null);
            for ($i = 0; $i < 7 && $dayCount <= $daysInMonth; $i++) {
                $calendar[$weekCount][$i] = [
                    'day' => $dayCount,
                    'events' => $eventsMap[$dayCount] ?? [],
                    'isToday' => $this->isToday($year, $month, $dayCount),
                ];
                $dayCount++;
            }
            $weekCount++;
        }
        
        // Get next and previous months
        $prevMonth = clone $firstDayOfMonth;
        $prevMonth->modify('-1 month');
        $nextMonth = clone $firstDayOfMonth;
        $nextMonth->modify('+1 month');
        
        return [
            'calendar' => $calendar,
            'monthName' => $firstDayOfMonth->format('F'),
            'year' => $year,
            'month' => $month,
            'prevMonth' => (int)$prevMonth->format('n'),
            'prevYear' => (int)$prevMonth->format('Y'),
            'nextMonth' => (int)$nextMonth->format('n'),
            'nextYear' => (int)$nextMonth->format('Y'),
            'totalEvents' => count($events),
        ];
    }
    
    /**
     * Check if a date is today
     */
    private function isToday(int $year, int $month, int $day): bool
    {
        $date = new \DateTime("$year-$month-$day");
        $today = new \DateTime();
        
        return $date->format('Y-m-d') === $today->format('Y-m-d');
    }
    
    /**
     * Generate iCalendar data for an event
     */
    public function generateICalForEvent(Event $event): string
    {
        $startDate = $event->getEventDate()->format('Ymd\THis\Z');
        
        // Assuming event duration is 2 hours
        $endDate = clone $event->getEventDate();
        $endDate->modify('+2 hours');
        $endDateFormatted = $endDate->format('Ymd\THis\Z');
        
        $location = $event->getLocationWeather() ? 
            $event->getLocationWeather()->getName() . ', ' . $event->getLocationWeather()->getRegion() : 
            'Location TBD';
            
        $description = strip_tags($event->getDescription());
        
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//Nautic Club//Event Calendar//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . md5($event->getId() . $event->getName()) . "@nautic-club.com\r\n";
        $ical .= "DTSTAMP:" . date('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . $startDate . "\r\n";
        $ical .= "DTEND:" . $endDateFormatted . "\r\n";
        $ical .= "SUMMARY:" . $this->escapeICalText($event->getName()) . "\r\n";
        $ical .= "DESCRIPTION:" . $this->escapeICalText($description) . "\r\n";
        $ical .= "LOCATION:" . $this->escapeICalText($location) . "\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";
        
        return $ical;
    }
    
    /**
     * Escape text for iCalendar format
     */
    private function escapeICalText(string $text): string
    {
        $text = str_replace("\r\n", "\\n", $text);
        $text = str_replace("\n", "\\n", $text);
        $text = str_replace(",", "\\,", $text);
        $text = str_replace(";", "\\;", $text);
        
        return $text;
    }
} 