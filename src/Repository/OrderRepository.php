<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return Order[] All orders, newest first. */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Query builder for the admin order list with optional filters.
     * Suitable for KnpPaginator.
     *
     * @param array{status?:?string,q?:?string,from?:?string,to?:?string} $filters
     */
    public function filteredQuery(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->addOrderBy('o.id', 'DESC');

        if (!empty($filters['status'])) {
            $qb->andWhere('o.status = :status')->setParameter('status', $filters['status']);
        }
        if (!empty($filters['q'])) {
            $qb->andWhere('o.customer_name LIKE :q OR o.customer_phone LIKE :q OR o.customer_email LIKE :q OR o.id = :qid')
                ->setParameter('q', '%' . $filters['q'] . '%')
                ->setParameter('qid', (int) $filters['q']);
        }
        if (!empty($filters['from'])) {
            $from = \DateTime::createFromFormat('Y-m-d', $filters['from']);
            if ($from) {
                $from->setTime(0, 0, 0);
                $qb->andWhere('o.createdAt >= :from')->setParameter('from', $from);
            }
        }
        if (!empty($filters['to'])) {
            $to = \DateTime::createFromFormat('Y-m-d', $filters['to']);
            if ($to) {
                $to->setTime(23, 59, 59);
                $qb->andWhere('o.createdAt <= :to')->setParameter('to', $to);
            }
        }

        return $qb;
    }

    /** Whether a user has an order containing the given product. */
    public function userBoughtProduct($user, $product): bool
    {
        $n = (int) $this->createQueryBuilder('o')
            ->select('COUNT(i.id)')
            ->join('o.items', 'i')
            ->andWhere('o.user = :u')->setParameter('u', $user)
            ->andWhere('i.product = :p')->setParameter('p', $product)
            ->getQuery()->getSingleScalarResult();

        return $n > 0;
    }

    /** Total revenue from completed orders. */
    public function totalRevenue(): float
    {
        return (float) $this->createQueryBuilder('o')
            ->select('COALESCE(SUM(o.total_price), 0)')
            ->andWhere('o.status = :s')->setParameter('s', Order::STATUS_COMPLETED)
            ->getQuery()->getSingleScalarResult();
    }

    /** @return array<string,int> status => count */
    public function countByStatusMap(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.status AS status', 'COUNT(o.id) AS cnt')
            ->groupBy('o.status')
            ->getQuery()->getScalarResult();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['status'] ?? 'pending'] = (int) $r['cnt'];
        }

        return $map;
    }

    /**
     * Revenue bucketed per day for the last $days days (zero-filled).
     *
     * @return array<string,float> 'Y-m-d' => revenue
     */
    public function revenueByDay(int $days = 14): array
    {
        $from = new \DateTime('-' . ($days - 1) . ' days');
        $from->setTime(0, 0, 0);

        $rows = $this->createQueryBuilder('o')
            ->select('o.createdAt AS createdAt', 'o.total_price AS total')
            ->andWhere('o.createdAt >= :from')->setParameter('from', $from)
            ->getQuery()->getScalarResult();

        $buckets = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $buckets[(new \DateTime("-$i days"))->format('Y-m-d')] = 0.0;
        }
        foreach ($rows as $r) {
            $d = $r['createdAt'] instanceof \DateTimeInterface
                ? $r['createdAt']->format('Y-m-d')
                : substr((string) $r['createdAt'], 0, 10);
            if (isset($buckets[$d])) {
                $buckets[$d] += (float) $r['total'];
            }
        }

        return $buckets;
    }

    /** @return Order[] Newest orders. */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')->addOrderBy('o.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /** @return Order[] Orders belonging to a given user, newest first. */
    public function findByUser($user): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Order[] Returns an array of Order objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Order
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
