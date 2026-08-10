<?php

namespace App\Repository;

use App\Entity\Review;
use App\Entity\SanPham;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /** @return Review[] Reviews for a product, newest first. */
    public function findByProduct(SanPham $product): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.product = :product')
            ->setParameter('product', $product)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Batch average/count for many products in one query (avoids N+1 on listings).
     *
     * @param int[] $productIds
     * @return array<int, array{average: float, count: int}>
     */
    public function getRatingsMap(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.product) AS pid', 'AVG(r.rating) AS average', 'COUNT(r.id) AS count')
            ->andWhere('r.product IN (:ids)')
            ->setParameter('ids', $productIds)
            ->groupBy('r.product')
            ->getQuery()
            ->getScalarResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['pid']] = [
                'average' => round((float) $row['average'], 1),
                'count' => (int) $row['count'],
            ];
        }

        return $map;
    }

    /**
     * @return array{average: float|null, count: int}
     */
    public function getRatingSummary(SanPham $product): array
    {
        $row = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS average', 'COUNT(r.id) AS count')
            ->andWhere('r.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getSingleResult();

        return [
            'average' => $row['average'] !== null ? round((float) $row['average'], 1) : null,
            'count' => (int) $row['count'],
        ];
    }
}
