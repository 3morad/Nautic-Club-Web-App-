<?php

namespace App\Repository;

use App\Entity\EmailTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailTemplate>
 *
 * @method EmailTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailTemplate[]    findAll()
 * @method EmailTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailTemplate::class);
    }

    /**
     * Find templates by category
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.category = :category')
            ->setParameter('category', $category)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search templates by name, code, subject or description
     */
    public function search(string $term): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.name LIKE :term OR t.code LIKE :term OR t.description LIKE :term OR t.subject LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get template categories with counts
     */
    public function getCategoriesWithCount(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('t.category, COUNT(t.id) as count')
            ->groupBy('t.category')
            ->getQuery()
            ->getResult();

        // Format the result as a simple array
        $categories = [];
        foreach ($result as $row) {
            $categories[$row['category']] = $row['count'];
        }

        return $categories;
    }
    
    /**
     * Find most recent templates
     */
    public function findMostRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find templates by multiple categories
     */
    public function findByCategories(array $categories): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.category IN (:categories)')
            ->setParameter('categories', $categories)
            ->orderBy('t.category', 'ASC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Get all distinct categories
     */
    public function getAllCategories(): array
    {
        $result = $this->createQueryBuilder('t')
            ->select('DISTINCT t.category')
            ->orderBy('t.category', 'ASC')
            ->getQuery()
            ->getScalarResult();
            
        return array_column($result, 'category');
    }
    
    /**
     * Advanced search with filters
     */
    public function advancedSearch(array $filters): array
    {
        $qb = $this->createQueryBuilder('t');
        
        // Search term
        if (!empty($filters['term'])) {
            $qb->andWhere('t.name LIKE :term OR t.code LIKE :term OR t.description LIKE :term OR t.subject LIKE :term')
               ->setParameter('term', '%' . $filters['term'] . '%');
        }
        
        // Categories filter
        if (!empty($filters['categories'])) {
            $qb->andWhere('t.category IN (:categories)')
               ->setParameter('categories', $filters['categories']);
        }
        
        // Date range filter (created)
        if (!empty($filters['createdFrom'])) {
            $qb->andWhere('t.createdAt >= :createdFrom')
               ->setParameter('createdFrom', $filters['createdFrom']);
        }
        
        if (!empty($filters['createdTo'])) {
            $qb->andWhere('t.createdAt <= :createdTo')
               ->setParameter('createdTo', $filters['createdTo']);
        }
        
        // Date range filter (updated)
        if (!empty($filters['updatedFrom'])) {
            $qb->andWhere('t.updatedAt >= :updatedFrom')
               ->setParameter('updatedFrom', $filters['updatedFrom']);
        }
        
        if (!empty($filters['updatedTo'])) {
            $qb->andWhere('t.updatedAt <= :updatedTo')
               ->setParameter('updatedTo', $filters['updatedTo']);
        }
        
        // Sorting
        $sortField = $filters['sortBy'] ?? 'name';
        $sortDirection = $filters['sortDirection'] ?? 'ASC';
        
        // Validate sort field
        if (!in_array($sortField, ['name', 'code', 'category', 'createdAt', 'updatedAt'])) {
            $sortField = 'name';
        }
        
        // Validate sort direction
        if (!in_array($sortDirection, ['ASC', 'DESC'])) {
            $sortDirection = 'ASC';
        }
        
        $qb->orderBy('t.' . $sortField, $sortDirection);
        
        // Apply limit and offset for pagination
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $qb->setMaxResults($filters['limit']);
            
            if (isset($filters['offset']) && $filters['offset'] >= 0) {
                $qb->setFirstResult($filters['offset']);
            }
        }
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Count templates matching advanced search criteria
     */
    public function countAdvancedSearch(array $filters): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');
        
        // Search term
        if (!empty($filters['term'])) {
            $qb->andWhere('t.name LIKE :term OR t.code LIKE :term OR t.description LIKE :term OR t.subject LIKE :term')
               ->setParameter('term', '%' . $filters['term'] . '%');
        }
        
        // Categories filter
        if (!empty($filters['categories'])) {
            $qb->andWhere('t.category IN (:categories)')
               ->setParameter('categories', $filters['categories']);
        }
        
        // Date range filter (created)
        if (!empty($filters['createdFrom'])) {
            $qb->andWhere('t.createdAt >= :createdFrom')
               ->setParameter('createdFrom', $filters['createdFrom']);
        }
        
        if (!empty($filters['createdTo'])) {
            $qb->andWhere('t.createdAt <= :createdTo')
               ->setParameter('createdTo', $filters['createdTo']);
        }
        
        // Date range filter (updated)
        if (!empty($filters['updatedFrom'])) {
            $qb->andWhere('t.updatedAt >= :updatedFrom')
               ->setParameter('updatedFrom', $filters['updatedFrom']);
        }
        
        if (!empty($filters['updatedTo'])) {
            $qb->andWhere('t.updatedAt <= :updatedTo')
               ->setParameter('updatedTo', $filters['updatedTo']);
        }
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
} 