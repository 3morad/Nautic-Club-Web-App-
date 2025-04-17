<?php

namespace App\Repository;

use App\Entity\LocationWeather;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LocationWeather>
 *
 * @method LocationWeather|null find($id, $lockMode = null, $lockVersion = null)
 * @method LocationWeather|null findOneBy(array $criteria, array $orderBy = null)
 * @method LocationWeather[]    findAll()
 * @method LocationWeather[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocationWeatherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LocationWeather::class);
    }

    /**
     * Find locations within a certain radius of a point using the Haversine formula
     * 
     * @param float $latitude Center point latitude
     * @param float $longitude Center point longitude
     * @param float $radius Radius in kilometers
     * @return LocationWeather[] Returns an array of LocationWeather objects
     */
    public function findByProximity(float $latitude, float $longitude, float $radius): array
    {
        // Earth's radius in kilometers
        $earthRadius = 6371;
        
        // Convert latitude and longitude from degrees to radians
        $latRad = deg2rad($latitude);
        $lonRad = deg2rad($longitude);
        
        // Haversine formula calculation using DQL
        $dql = "
            SELECT l, 
                   ($earthRadius * ACOS(
                       COS(:latRad) * COS(RADIANS(l.latitude)) * COS(RADIANS(l.longitude) - :lonRad) + 
                       SIN(:latRad) * SIN(RADIANS(l.latitude))
                   )) AS distance
            FROM App\Entity\LocationWeather l
            HAVING distance <= :radius
            ORDER BY distance ASC
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
     * Updates the weather data for a location
     */
    public function save(LocationWeather $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Removes a location and its weather data
     */
    public function remove(LocationWeather $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 