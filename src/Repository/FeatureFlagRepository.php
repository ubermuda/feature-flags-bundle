<?php

namespace Ubermuda\FeatureFlagsBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Ubermuda\FeatureFlagsBundle\Entity\FeatureFlag;

/** @extends ServiceEntityRepository<FeatureFlag> */
class FeatureFlagRepository extends ServiceEntityRepository
{
    private const array ALLOWED_SORTS = ['name', 'type'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeatureFlag::class);
    }

    /** @return array<string, FeatureFlag> */
    public function findAllIndexed(): array
    {
        $indexed = [];
        foreach ($this->findAll() as $flag) {
            $indexed[$flag->name] = $flag;
        }

        return $indexed;
    }

    /**
     * @return list<FeatureFlag>
     */
    public function findByTag(string $tag): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT id FROM feature_flag WHERE tags @> :tag::jsonb';
        $ids = $conn->fetchFirstColumn($sql, ['tag' => json_encode([$tag])]);

        if ([] === $ids) {
            return [];
        }

        return $this->createQueryBuilder('ff')
            ->andWhere('ff.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * Combined paginated query for the admin list: case-insensitive name search,
     * optional type and tag filters, ordered by an allow-listed column.
     *
     * @param string $search Substring matched against flag name (case-insensitive)
     * @param string $type   FeatureFlagType backing value, or '' for any
     * @param string $tag    Tag to filter by, or '' for any
     *
     * @return Paginator<FeatureFlag>
     */
    public function findPaginated(int $page, int $limit, string $sort, string $dir, string $search, string $type, string $tag): Paginator
    {
        $sort = in_array($sort, self::ALLOWED_SORTS, true) ? $sort : 'name';
        $dir = 'ASC' === strtoupper($dir) ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('ff');

        if ('' !== $search) {
            $qb->andWhere('LOWER(ff.name) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search.'%');
        }

        if ('' !== $type) {
            $qb->andWhere('ff.type = :type')->setParameter('type', $type);
        }

        if ('' !== $tag) {
            $conn = $this->getEntityManager()->getConnection();
            $ids = $conn->fetchFirstColumn(
                'SELECT id FROM feature_flag WHERE tags @> :tag::jsonb',
                ['tag' => json_encode([$tag])],
            );
            if ([] === $ids) {
                $qb->andWhere('1 = 0');
            } else {
                $qb->andWhere('ff.id IN (:ids)')->setParameter('ids', $ids);
            }
        }

        $qb->orderBy('ff.'.$sort, $dir)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return new Paginator($qb);
    }

    /**
     * Returns all distinct tags used across all feature flags, sorted alphabetically.
     *
     * @return list<string>
     */
    public function findAllTags(): array
    {
        $flags = $this->findAll();
        $tagArrays = array_map(fn (FeatureFlag $f): array => $f->tags, $flags);
        $merged = [] === $tagArrays ? [] : array_merge(...$tagArrays);
        $tags = array_values(array_unique($merged));
        sort($tags);

        return $tags;
    }
}
