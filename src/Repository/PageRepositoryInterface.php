<?php

namespace Pushword\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Pushword\Core\Entity\PageInterface;

/**
 * @template T as PageInterface
 * @template TEntityClass as PageInterface
 * @extends Selectable<int, PageInterface>
 * @extends ObjectRepository<PageInterface>
 */
interface PageRepositoryInterface extends ServiceEntityRepositoryInterface, ObjectRepository, Selectable
{
    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy the index for the from
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null);

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
    );

    /**
     * Can be used via a twig function.
     *
     * @param string|array<string> $host
     * @param array<(string|int), string> $orderBy
     * @param array<mixed>     $where
     * @param int|array<mixed> $limit
     */
    public function getPublishedPageQueryBuilder($host = '', array $where = [], array $orderBy = [], $limit = 0): QueryBuilder;

    /**
     * @param string|string[] $host
     */
    public function getPage(string $slug, $host, bool $checkId = true): ?PageInterface;

    public function getIndexablePagesQuery(
        string $host,
        string $locale,
        ?int $limit = null
    ): QueryBuilder;

    /**
     * @return PageInterface[]
     */
    public function getPagesWithoutParent(): array;

    /**
     * @return PageInterface[]
     */
    public function getPagesUsingMedia(string $media): array;

    /**
     * @param string|string[] $host
     */
    public function andHost(QueryBuilder $qb, $host): QueryBuilder;

    /**
     * @param string|string[] $host
     *
     * @return PageInterface[]
     */
    public function findByHost($host): array;
}
