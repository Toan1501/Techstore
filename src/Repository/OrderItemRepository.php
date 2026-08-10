<?php

namespace App\Repository;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderItem>
 */
class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    /** @return array<int, array{name: string, qty: int, revenue: float}> Best-selling products. */
    public function bestSellers(int $limit = 10): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('p.name AS name', 'SUM(i.quantity) AS qty', 'SUM(i.price * i.quantity) AS revenue')
            ->join('i.product', 'p')
            ->groupBy('p.id')
            ->orderBy('qty', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();

        return array_map(fn ($r) => ['name' => $r['name'], 'qty' => (int) $r['qty'], 'revenue' => (float) $r['revenue']], $rows);
    }

    /** @return array<int, array{name: string, revenue: float}> Revenue grouped by category. */
    public function revenueByCategory(): array
    {
        $rows = $this->createQueryBuilder('i')
            ->select('c.name AS name', 'SUM(i.price * i.quantity) AS revenue')
            ->join('i.product', 'p')
            ->join('p.cate', 'c')
            ->groupBy('c.id')
            ->orderBy('revenue', 'DESC')
            ->getQuery()->getResult();

        return array_map(fn ($r) => ['name' => $r['name'], 'revenue' => (float) $r['revenue']], $rows);
    }
}
