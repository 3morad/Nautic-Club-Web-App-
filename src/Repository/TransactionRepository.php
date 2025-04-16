<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function save(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('t')
            ->select('SUM(t.amount) as total')
            ->where('t.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0.0;
    }

    public function getMonthlyRevenue(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUBSTRING(t.createdAt, 1, 7) as month')
            ->addSelect('SUM(t.amount) as revenue')
            ->where('t.status = :status')
            ->andWhere('t.createdAt >= :sixMonthsAgo')
            ->setParameter('status', 'completed')
            ->setParameter('sixMonthsAgo', new \DateTime('-6 months'))
            ->groupBy('month')
            ->orderBy('month', 'ASC');

        $results = $qb->getQuery()->getResult();

        // Format the results
        return array_map(function($row) {
            return [
                'month' => date('M Y', strtotime($row['month'] . '-01')),
                'revenue' => (float) $row['revenue']
            ];
        }, $results);
    }
} 