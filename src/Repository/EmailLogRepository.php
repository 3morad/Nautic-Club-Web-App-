<?php

namespace App\Repository;

use App\Entity\EmailLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailLog>
 *
 * @method EmailLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailLog[]    findAll()
 * @method EmailLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailLog::class);
    }

    /**
     * Find email logs for a user
     */
    public function findByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :user')
            ->setParameter('user', $user)
            ->orderBy('l.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find email logs for a template
     */
    public function findByTemplate(string $templateCode, int $limit = 50): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.emailTemplate = :template')
            ->setParameter('template', $templateCode)
            ->orderBy('l.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get email stats for a period
     */
    public function getStatsByPeriod(\DateTime $startDate, \DateTime $endDate): array
    {
        $result = $this->createQueryBuilder('l')
            ->select('l.status, COUNT(l.id) as count')
            ->andWhere('l.sentAt BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->groupBy('l.status')
            ->getQuery()
            ->getResult();

        // Format the result
        $stats = [
            'sent' => 0,
            'failed' => 0,
            'total' => 0,
            'last24h' => 0,
            'successRate' => 100,
        ];

        foreach ($result as $row) {
            if ($row['status'] === 'sent') {
                $stats['sent'] = $row['count'];
            } elseif ($row['status'] === 'failed') {
                $stats['failed'] = $row['count'];
            }
            $stats['total'] += $row['count'];
        }

        // Calculate success rate
        if ($stats['total'] > 0) {
            $stats['successRate'] = round(($stats['sent'] / $stats['total']) * 100);
        }

        // Get last 24 hours count
        $yesterday = new \DateTime('-24 hours');
        $today = new \DateTime();
        $last24hResult = $this->createQueryBuilder('l')
            ->select('COUNT(l.id) as count')
            ->andWhere('l.sentAt BETWEEN :start AND :end')
            ->setParameter('start', $yesterday)
            ->setParameter('end', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $stats['last24h'] = $last24hResult;

        return $stats;
    }

    /**
     * Get most recent logs with pagination
     */
    public function findRecent(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('l')
            ->orderBy('l.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total logs
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get top email templates by usage
     */
    public function getTopTemplates(int $limit = 5): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.emailTemplate as template, COUNT(l.id) as count, SUM(CASE WHEN l.status = :sent THEN 1 ELSE 0 END) as sent')
            ->setParameter('sent', 'sent')
            ->groupBy('l.emailTemplate')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
} 