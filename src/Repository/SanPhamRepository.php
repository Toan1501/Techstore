<?php

namespace App\Repository;

use App\Entity\SanPham;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<SanPham>
 *
 * @method SanPham|null find($id, $lockMode = null, $lockVersion = null)
 * @method SanPham|null findOneBy(array $criteria, array $orderBy = null)
 * @method SanPham[]    findAll()
 * @method SanPham[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SanPhamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SanPham::class);
    }

    public function save(SanPham $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SanPham $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** Allowed sort keys for the product listing. */
    public const SORTS = ['newest', 'price_asc', 'price_desc', 'name'];

    /** @return SanPham[] All products, newest first. */
    public function findAllNewestFirst(): array
    {
        return $this->findBy([], ['id' => 'DESC']);
    }

    /** Build a Query for all products ordered by the given (already validated) sort key. */
    public function queryBySort(string $sort): Query
    {
        return $this->queryFiltered(['sort' => $sort]);
    }

    /**
     * Build a filtered + sorted Query for the product listing.
     * Supported keys: category (Category|id), min_price, max_price, in_stock (bool), sort.
     */
    public function queryFiltered(array $filters): Query
    {
        $qb = $this->createQueryBuilder('sp');

        if (!empty($filters['category'])) {
            $qb->andWhere('sp.cate = :cat')->setParameter('cat', $filters['category']);
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== '' && $filters['min_price'] !== null) {
            $qb->andWhere('sp.price >= :minp')->setParameter('minp', (float) $filters['min_price']);
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== '' && $filters['max_price'] !== null) {
            $qb->andWhere('sp.price <= :maxp')->setParameter('maxp', (float) $filters['max_price']);
        }
        if (!empty($filters['in_stock'])) {
            $qb->andWhere('sp.stock > 0');
        }
        if (!empty($filters['brand'])) {
            // One brand or a list (multi-select filter).
            $qb->andWhere('sp.brand IN (:brands)')->setParameter('brands', array_values((array) $filters['brand']));
        }
        if (!empty($filters['min_rating'])) {
            $qb->andWhere('(SELECT AVG(rv.rating) FROM App\Entity\Review rv WHERE rv.product = sp) >= :minRating')
                ->setParameter('minRating', (float) $filters['min_rating']);
        }
        // Keyword(s) matched against the product name (string or array → OR).
        if (!empty($filters['q'])) {
            $ors = [];
            foreach (array_values((array) $filters['q']) as $i => $term) {
                $ors[] = "sp.name LIKE :q$i";
                $qb->setParameter("q$i", '%' . $term . '%');
            }
            $qb->andWhere('(' . implode(' OR ', $ors) . ')');
        }
        // Only products that currently have a strike-through price.
        if (!empty($filters['discount_only'])) {
            $qb->andWhere('sp.priceOld IS NOT NULL')->andWhere('sp.priceOld > sp.price');
        }

        switch ($filters['sort'] ?? 'newest') {
            case 'discount':
                $qb->addSelect('((sp.priceOld - sp.price) / sp.priceOld) AS HIDDEN pct')->orderBy('pct', 'DESC');
                break;
            case 'price_asc':
                $qb->orderBy('sp.price', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('sp.price', 'DESC');
                break;
            case 'name':
                $qb->orderBy('sp.name', 'ASC');
                break;
            default:
                $qb->orderBy('sp.id', 'DESC');
        }

        return $qb->getQuery();
    }

    /** @return string[] Distinct non-empty brand names. */
    public function distinctBrands(): array
    {
        $rows = $this->createQueryBuilder('sp')
            ->select('DISTINCT sp.brand AS brand')
            ->andWhere('sp.brand IS NOT NULL')->andWhere("sp.brand != ''")
            ->orderBy('sp.brand', 'ASC')
            ->getQuery()->getScalarResult();

        return array_column($rows, 'brand');
    }

    /** @return array<string,int> brand => product count, most products first. */
    public function brandCounts(): array
    {
        $rows = $this->createQueryBuilder('sp')
            ->select('sp.brand AS brand', 'COUNT(sp.id) AS n')
            ->andWhere('sp.brand IS NOT NULL')->andWhere("sp.brand != ''")
            ->groupBy('sp.brand')->orderBy('n', 'DESC')->addOrderBy('sp.brand', 'ASC')
            ->getQuery()->getScalarResult();

        $out = [];
        foreach ($rows as $r) {
            $out[$r['brand']] = (int) $r['n'];
        }

        return $out;
    }

    /**
     * Brand => product count, optionally limited to one category.
     *
     * @return array<string,int>
     */
    public function brandCountsFor($category = null): array
    {
        $qb = $this->createQueryBuilder('sp')
            ->select('sp.brand AS brand', 'COUNT(sp.id) AS n')
            ->andWhere('sp.brand IS NOT NULL')->andWhere("sp.brand != ''")
            ->groupBy('sp.brand')->orderBy('n', 'DESC')->addOrderBy('sp.brand', 'ASC');
        if ($category) {
            $qb->andWhere('sp.cate = :cat')->setParameter('cat', $category);
        }

        $out = [];
        foreach ($qb->getQuery()->getScalarResult() as $r) {
            $out[$r['brand']] = (int) $r['n'];
        }

        return $out;
    }

    /**
     * Category id => [brand => count] for every category in one query (mega menu).
     *
     * @return array<int, array<string,int>>
     */
    public function brandCountsByCategory(): array
    {
        $rows = $this->createQueryBuilder('sp')
            ->select('IDENTITY(sp.cate) AS cat', 'sp.brand AS brand', 'COUNT(sp.id) AS n')
            ->andWhere('sp.brand IS NOT NULL')->andWhere("sp.brand != ''")
            ->groupBy('cat', 'sp.brand')->orderBy('n', 'DESC')->addOrderBy('sp.brand', 'ASC')
            ->getQuery()->getScalarResult();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['cat']][$r['brand']] = (int) $r['n'];
        }

        return $out;
    }

    /**
     * Load the gallery images of many products in one query (avoids one query per card).
     * Doctrine fills the still-uninitialized collections of the already-managed entities.
     */
    public function preloadImages(iterable $products): void
    {
        $ids = [];
        foreach ($products as $p) {
            if ($p instanceof SanPham && $p->getId()) {
                $ids[] = $p->getId();
            }
        }
        if (!$ids) {
            return;
        }
        $this->createQueryBuilder('sp')
            ->select('sp', 'img')
            ->leftJoin('sp.images', 'img')
            ->andWhere('sp.id IN (:ids)')->setParameter('ids', array_values(array_unique($ids)))
            ->getQuery()->getResult();
    }

    /** @return SanPham[] Biggest discounts first (flash sale). */
    public function findTopDeals(int $limit = 12): array
    {
        return $this->queryFiltered(['discount_only' => true, 'sort' => 'discount'])
            ->setMaxResults($limit)->getResult();
    }

    /** @return array{min: float, max: float} Lowest and highest product price. */
    public function priceBounds(): array
    {
        $row = $this->createQueryBuilder('sp')
            ->select('MIN(sp.price) AS mn', 'MAX(sp.price) AS mx')
            ->getQuery()
            ->getSingleResult();

        return [
            'min' => (float) ($row['mn'] ?? 0),
            'max' => (float) ($row['mx'] ?? 0),
        ];
    }

    /** @return SanPham[] Products at or below a low-stock threshold. */
    public function findLowStock(int $threshold = 5, int $limit = 10): array
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.stock <= :t')->setParameter('t', $threshold)
            ->orderBy('sp.stock', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /** @return SanPham[] Discounted products (priceOld set and higher than price). */
    public function findDeals(int $limit = 6): array
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.priceOld IS NOT NULL')
            ->andWhere('sp.priceOld > sp.price')
            ->orderBy('sp.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return SanPham[] Up to $limit products in the same category, excluding $product. */
    public function findRelated(SanPham $product, int $limit = 4): array
    {
        if (!$product->getCate()) {
            return [];
        }

        return $this->createQueryBuilder('sp')
            ->andWhere('sp.cate = :cat')->setParameter('cat', $product->getCate())
            ->andWhere('sp.id != :id')->setParameter('id', $product->getId())
            ->orderBy('sp.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByKeyword($keyword)
    {
        return $this->queryByKeyword($keyword)->getResult();
    }

    /** Query for products matching a keyword in name or brand (for pagination). */
    public function queryByKeyword(?string $keyword): Query
    {
        return $this->createQueryBuilder('s')
            ->where('s.name LIKE :keyword OR s.brand LIKE :keyword')
            ->setParameter('keyword', '%'.$keyword.'%')
            ->orderBy('s.id', 'DESC')
            ->getQuery();
    }
    
    public function findByKeywordWithLimit($keyword,$limit=5,$page=1)
    {
        return $this->createQueryBuilder('s')
            ->where('s.name LIKE :keyword')
            ->setParameter('keyword', '%'.$keyword.'%')
            ->setMaxResults($limit)
            ->setFirstResult(($page-1)*$limit)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return SanPham[] Returns an array of SanPham objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SanPham
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
