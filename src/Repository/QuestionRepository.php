<?php

namespace App\Repository;

use App\Entity\Question;
use App\Entity\SanPham;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /** @return Question[] */
    public function findByProduct(SanPham $product): array
    {
        return $this->findBy(['product' => $product], ['createdAt' => 'DESC']);
    }

    /** @return Question[] All, unanswered first, newest first (admin). */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('q')
            ->orderBy('q.answer', 'ASC') // NULLs first on MySQL
            ->addOrderBy('q.createdAt', 'DESC')
            ->getQuery()->getResult();
    }
}
