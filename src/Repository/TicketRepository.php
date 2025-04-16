<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 *
 * @method Ticket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticket|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ticket[]    findAll()
 * @method Ticket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function save(Ticket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Ticket $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getMonthlyTicketSales(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUBSTRING(t.createdAt, 1, 7) as month')
            ->addSelect('COUNT(t.id) as tickets')
            ->where('t.createdAt >= :sixMonthsAgo')
            ->setParameter('sixMonthsAgo', new \DateTime('-6 months'))
            ->groupBy('month')
            ->orderBy('month', 'ASC');

        $results = $qb->getQuery()->getResult();

        // Format the results
        return array_map(function($row) {
            return [
                'month' => date('M Y', strtotime($row['month'] . '-01')),
                'tickets' => (int) $row['tickets']
            ];
        }, $results);
    }
} 