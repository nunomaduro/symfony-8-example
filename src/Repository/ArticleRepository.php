<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Generate a unique slug, appending -2, -3, etc. if needed.
     */
    public function uniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $candidate = $slug;
        $i = 1;

        while (true) {
            $qb = $this->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.slug = :slug')
                ->setParameter('slug', $candidate);

            if ($excludeId !== null) {
                $qb->andWhere('a.id != :id')->setParameter('id', $excludeId);
            }

            if ((int) $qb->getQuery()->getSingleScalarResult() === 0) {
                return $candidate;
            }

            $candidate = $slug.'-'.++$i;
        }
    }

    /**
     * @return Article[]
     */
    public function findAllOrderedByNewest(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
