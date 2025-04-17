<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Find events occurring at locations within a certain radius of a given point
     * 
     * @param float $latitude Center point latitude
     * @param float $longitude Center point longitude
     * @param float $radius Radius in kilometers
     * @return Event[] Returns an array of Event objects
     */
    public function findEventsByProximity(float $latitude, float $longitude, float $radius): array
    {
        // Earth's radius in kilometers
        $earthRadius = 6371;
        
        // Convert latitude and longitude from degrees to radians
        $latRad = deg2rad($latitude);
        $lonRad = deg2rad($longitude);
        
        // Haversine formula calculation using DQL
        $dql = "
            SELECT e, 
                   ($earthRadius * ACOS(
                       COS(:latRad) * COS(RADIANS(l.latitude)) * COS(RADIANS(l.longitude) - :lonRad) + 
                       SIN(:latRad) * SIN(RADIANS(l.latitude))
                   )) AS distance
            FROM App\Entity\Event e
            JOIN e.locationWeather l
            HAVING distance <= :radius
            ORDER BY e.eventDate ASC, distance ASC
        ";
        
        // Create and execute the query
        $query = $this->getEntityManager()->createQuery($dql);
        $query->setParameters([
            'latRad' => $latRad,
            'lonRad' => $lonRad,
            'radius' => $radius
        ]);
        
        return $query->getResult();
    }

    /**
     * Find upcoming events by weather condition
     * 
     * @param string $weatherCondition The desired weather condition (e.g., 'Sunny', 'Rainy')
     * @return Event[] Returns an array of Event objects
     */
    public function findUpcomingEventsByWeather(string $weatherCondition): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.locationWeather', 'l')
            ->where('l.weatherCondition = :weatherCondition')
            ->andWhere('e.eventDate > :now')
            ->setParameter('weatherCondition', $weatherCondition)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.eventDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Saves an event to the database
     */
    public function save(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Removes an event from the database
     */
    public function remove(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 