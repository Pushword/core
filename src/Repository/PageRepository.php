<?php

namespace Pushword\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Pushword\Core\Entity\PageInterface;

/**
 * @method PageInterface|null                        find($id, $lockMode = null, $lockVersion = null)
 * @method PageInterface|null                        findOneBy(array $criteria, array $orderBy = null)
 * @method list<\Pushword\Core\Entity\PageInterface> findAll()
 * @method list<\Pushword\Core\Entity\PageInterface> findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageRepository extends ServiceEntityRepository implements PageRepositoryInterface
{
    protected bool $hostCanBeNull = false;

    /**
     * Can be used via a twig function.
     *
     * @param string|array<string> $host
     * @param array<(string|int), string> $orderBy
     * @param array<mixed>     $where
     * @param int|array<mixed> $limit
     *
     * @return PageInterface[]
     */
    public function getPublishedPages(
        $host = '',
        array $where = [],
        array $orderBy = [],
        $limit = 0,
        bool $withRedirection = true
    ) {
        $queryBuilder = $this->getPublishedPageQueryBuilder($host, $where, $orderBy);

        if (! $withRedirection) {
            $this->andNotRedirection($queryBuilder);
        }

        $this->limit($queryBuilder, $limit);

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }

    /**
     * Can be used via a twig function.
     *
     * @param string|array<string> $host
     * @param array<(string|int), string> $orderBy
     * @param array<mixed>     $where
     * @param int|array<mixed> $limit
     */
    public function getPublishedPageQueryBuilder($host = '', array $where = [], array $orderBy = [], $limit = 0): QueryBuilder
    {
        $qb = $this->buildPublishedPageQuery('p');

        $this->andHost($qb, $host);
        $this->andWhere($qb, $where);
        $this->orderBy($qb, $orderBy);
        if ($limit) {
            $this->limit($qb, $limit);
        }

        return $qb;
    }

    private function buildPublishedPageQuery(string $alias = 'p'): QueryBuilder
    {
        //$this->andNotRedirection($queryBuilder);

        return $this->createQueryBuilder($alias)
            ->andWhere($alias.'.publishedAt <=  :now')
            ->setParameter('now', new \DateTime(), 'datetime')
            ->orderBy($alias.'.priority', Criteria::DESC)
            ->addOrderBy($alias.'.publishedAt', Criteria::DESC);
    }

    /**
     * @param string|string[] $host
     */
    public function getPage(string $slug, $host, bool $checkId = true): ?PageInterface
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.slug =  :slug')->setParameter('slug', $slug);

        if ((int) $slug > 0 && $checkId) {
            $qb->orWhere('p.id =  :id')->setParameter('id', $slug);
        }

        $qb = $this->andHost($qb, $host);

        return $qb->getQuery()->getResult()[0] ?? null;
    }

    /**
     * @param string|string[] $host
     *
     * @return PageInterface[]
     */
    public function findByHost($host): array
    {
        $qb = $this->createQueryBuilder('p');
        $this->andHost($qb, $host);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string|string[] $host
     *                              Return page for sitemap and main Feed (PageController)
     *                              $qb->getQuery()->getResult();
     */
    public function getIndexablePagesQuery(
        $host,
        string $locale,
        ?int $limit = null
    ): QueryBuilder {
        $qb = $this->buildPublishedPageQuery('p');
        $qb = $this->andIndexable($qb);
        $qb = $this->andHost($qb, $host);
        $qb = $this->andLocale($qb, $locale);

        $this->andNotRedirection($qb);

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb;
    }

    /**
     * Used in admin PageCrudController.
     *
     * @return PageInterface[]
     */
    public function getPagesWithoutParent(): array
    {
        $query = $this->createQueryBuilder('p')
            ->andWhere('p.parentPage is NULL')
            ->orderBy('p.slug', Criteria::DESC)
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Used in admin Media.
     */
    public function getPagesUsingMedia(string $media): array
    {
        $qb = $this->createQueryBuilder('p');

        $orx = $qb->expr()->orX();
        $orx->add($qb->expr()->like('p.mainContent', ':apostrophMedia')); // catch: 'example.jpg'
        $orx->add($qb->expr()->like('p.mainContent', ':quotedMedia')); // catch: "example.jpg'
        $orx->add($qb->expr()->like('p.mainContent', ':defaultMedia')); // catch: media/default/example.jpg
        $orx->add($qb->expr()->like('p.mainContent', ':thumbMedia'));
        $query = $qb->where($orx)->setParameters([
            'apostrophMedia' => '%\''.$media.'\'%',
            'quotedMedia' => '%"'.$media.'"%',
            'defaultMedia' => '/media/default/'.$media.'%',
            'thumbMedia' => '/media/thumb/'.$media.'%',
        ])->getQuery();

        return $query->getResult();
    }

    private function getRootAlias(QueryBuilder $queryBuilder): string
    {
        $aliases = $queryBuilder->getRootAliases();

        if (! isset($aliases[0])) {
            throw new \RuntimeException('No alias was set before invoking getRootAlias().');
        }

        return $aliases[0];
    }

    /* ~~~~~~~~~~~~~~~ Query Builder Helper ~~~~~~~~~~~~~~~ */

    /**
     * QueryBuilder Helper.
     *
     * @param array $where array containing array with key,operator,value,key_prefix
     *                     Eg:
     *                     ['title', 'LIKE' '%this%'] => works
     *                     [['title', 'LIKE' '%this%']] => works
     *                     [['key'=>'title', 'operator' => 'LIKE', 'value' => '%this%'], 'OR', ['key'=>'slug', 'operator' => 'LIKE', 'value' => '%this%']] => works
     *                     See confusing parenthesis DQL doctrine https://symfonycasts.com/screencast/doctrine-queries/and-where-or-where#avoid-orwhere-and-where
     */
    private function andWhere(QueryBuilder $queryBuilder, array $where): QueryBuilder
    {
        // Normalize array [']
        if (! empty($where) && (! isset($where[0]) || ! \is_array($where[0]))) {
            $where = [$where];
        }

        if (\in_array('OR', $where)) {
            return $this->andWhereOr($queryBuilder, $where);
        }

        foreach ($where as $singleWhere) {
            if (! \is_array($singleWhere)) {
                throw new Exception('malformated where params');
            }

            $this->simpleAndWhere($queryBuilder, $singleWhere);
        }

        return $queryBuilder;
    }

    private function andWhereOr(QueryBuilder $queryBuilder, array $where): QueryBuilder
    {
        $orX = $queryBuilder->expr()->orX();

        foreach ($where as $singleWhere) {
            if (! \is_array($singleWhere)) {
                continue;
            }

            $k = md5('a'.rand());
            $orX->add($queryBuilder->expr()->like(($singleWhere['key_prefix'] ?? $singleWhere[4] ?? 'p.').($singleWhere['key'] ?? $singleWhere[0]), ':m'.$k));
            $queryBuilder->setParameter('m'.$k, $singleWhere['value'] ?? $singleWhere[2]);
        }

        return $queryBuilder->andWhere($orX);
    }

    private function simpleAndWhere(QueryBuilder $queryBuilder, array $w): QueryBuilder
    {
        if (($w['value'] ?? $w[2]) === null) {
            return $queryBuilder->andWhere(
                ($w['key_prefix'] ?? $w[4] ?? 'p.').($w['key'] ?? $w[0]).
                    ' '.($w['operator'] ?? $w[1]).' NULL'
            );
        }

        $k = md5('a'.rand());

        return $queryBuilder->andWhere(
            ($w['key_prefix'] ?? $w[4] ?? 'p.').($w['key'] ?? $w[0])
                        .' '.($w['operator'] ?? $w[1])
                        .' :m'.$k
        )->setParameter('m'.$k, $w['value'] ?? $w[2]);
    }

    /**
     * @param array $orderBy containing key,direction
     */
    private function orderBy(QueryBuilder $queryBuilder, array $orderBy): QueryBuilder
    {
        if ([] === $orderBy) {
            return $queryBuilder;
        }

        $keys = explode(',', $orderBy['key'] ?? $orderBy[0]);
        foreach ($keys as $i => $key) {
            $direction = $this->extractDirection($key, $orderBy);
            $orderByFunc = 0 === $i ? 'orderBy' : 'addOrderBy';
            $queryBuilder->$orderByFunc($this->getRootAlias($queryBuilder).'.'.$key, $direction);
        }

        return $queryBuilder;
    }

    /**
     * @return mixed|string
     */
    private function extractDirection(&$key, $orderBy)
    {
        if (! str_contains($key, ' ')) {
            return $orderBy['direction'] ?? $orderBy[1] ?? 'DESC';
        }

        $keyDir = explode(' ', $key, 2);
        $key = $keyDir[0];

        return $keyDir[1];
    }

    /**
     * QueryBuilder Helper.
     *
     * @param string|array $host
     */
    public function andHost(QueryBuilder $queryBuilder, $host): QueryBuilder
    {
        if (! $host) {
            return $queryBuilder;
        }

        if (\is_string($host)) {
            $host = [$host];
        }

        return $queryBuilder->andWhere($this->getRootAlias($queryBuilder).'.host IN (:host)')
            ->setParameter('host', $host);
    }

    protected function andLocale(QueryBuilder $queryBuilder, string $locale): QueryBuilder
    {
        if ('' === $locale || '0' === $locale) {
            return $queryBuilder;
        }

        $alias = $this->getRootAlias($queryBuilder);

        return $queryBuilder->andWhere($alias.'.locale LIKE :locale')
                ->setParameter('locale', $locale);
    }

    protected function andIndexable(QueryBuilder $queryBuilder): QueryBuilder
    {
        $alias = $this->getRootAlias($queryBuilder);

        return $queryBuilder->andWhere($alias.'.metaRobots IS NULL OR '.$alias.'.metaRobots NOT LIKE :noi2')
            ->setParameter('noi2', '%noindex%');
    }

    protected function andNotRedirection(QueryBuilder $queryBuilder): QueryBuilder
    {
        $alias = $this->getRootAlias($queryBuilder);

        return $queryBuilder->andWhere($alias.'.mainContent NOT LIKE :noi')
            ->setParameter('noi', 'Location:%');
    }

    /**
     * Query Builder helper.
     *
     * @param int|array $limit containing start,max or just max
     */
    protected function limit($qb, $limit): QueryBuilder
    {
        if (! $limit) {
            return $qb;
        }

        if (\is_array($limit)) {
            return $qb->setFirstResult($limit['start'] ?? $limit[0])->setMaxResults($limit['max'] ?? $limit[1]);
        }

        return $qb->setMaxResults($limit);
    }
}
