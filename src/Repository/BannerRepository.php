<?php

namespace App\Repository;

use App\Entity\Banner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Banner>
 */
class BannerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Banner::class);
    }

    /** @return Banner[] Active banners ordered by position. */
    public function findActiveOrdered(): array
    {
        return $this->findBy(['active' => true], ['position' => 'ASC', 'id' => 'ASC']);
    }

    /** @return Banner[] All banners ordered by position (admin). */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['position' => 'ASC', 'id' => 'ASC']);
    }
}
