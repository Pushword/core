<?php

namespace Pushword\Core\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use LogicException;
use Pushword\Admin\PageCheatSheetAdmin;
use Pushword\Core\Entity\Media;
use Pushword\Core\Entity\Page;
use RuntimeException;

/**
 * @psalm-suppress MethodSignatureMustProvideReturnType
 *
 * @extends ServiceEntityRepository<Page>
 *
 * @method Page|null  find($id, $lockMode = null, $lockVersion = null)
 * @method Page|null  findOneBy(array $criteria, array $orderBy = null)
 * @method list<Page> findAll()
 * @method list<Page> findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @implements Selectable<int, Page>
 * @implements ObjectRepository<Page>
 */
class PageRepository extends ServiceEntityRepository implements ObjectRepository, Selectable
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Page::class);
    }

    protected bool $hostCanBeNull = false;

    /**
     * Can be used via a twig function.
     *
     * @param string|array<string>         $host
     * @param array<(string|int), string>  $orderBy
     * @param array<mixed>                 $where
     * @param int|array<(string|int), int> $limit
     *
     * @return Page[]
     */
    public function getPublishedPages(
        string|array $host = '',
        array $where = [],
        array $orderBy = [],
        int|array $limit = 0,
        bool $withRedirection = true
    ) {
        $queryBuilder = $this->getPublishedPageQueryBuilder($host, $where, $orderBy);

        if (! $withRedirection) {
            $this->andNotRedirection($queryBuilder);
        }

        $this->limit($queryBuilder, $limit);

        $query = $queryBuilder->getQuery();

        return $query->getResult(); // @phpstan-ignore-line
    }

    /**
     * Can be used via a twig function.
     *
     * @param string|array<string>         $host
     * @param array<(string|int), string>  $orderBy
     * @param array<mixed>                 $where
     * @param int|array<(string|int), int> $limit
     */
    public function getPublishedPageQueryBuilder(string|array $host = '', array $where = [], array $orderBy = [], int|array $limit = 0): QueryBuilder
    {
        $queryBuilder = $this->buildPublishedPageQuery('p');

        $this->andHost($queryBuilder, $host);
        (new FilterWhereParser($queryBuilder, $where))->parseAndAdd();
        $this->orderBy($queryBuilder, $orderBy);
        $this->limit($queryBuilder, $limit);

        return $queryBuilder;
    }

    private function buildPublishedPageQuery(string $alias = 'p'): QueryBuilder
    {
        // $this->andNotRedirection($queryBuilder);

        return $this->createQueryBuilder($alias)
            ->andWhere($alias.'.publishedAt <=  :now')
            ->setParameter('now', new DateTime(), 'datetime')
            ->andWhere($alias.'.slug <> :cheatsheet')
            ->setParameter('cheatsheet', PageCheatSheetAdmin::CHEATSHEET_SLUG);
    }

    /**
     * @param string|string[] $host
     */
    public function getPage(string $slug, string|array $host, bool $checkId = true): ?Page
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->andWhere('p.slug =  :slug')->setParameter('slug', $slug);

        if ((int) $slug > 0 && $checkId) {
            $queryBuilder->orWhere('p.id =  :id')->setParameter('id', $slug);
        }

        $queryBuilder = $this->andHost($queryBuilder, $host);

        return $queryBuilder->getQuery()->getResult()[0] ?? null;  // @phpstan-ignore-line
    }

    /**
     * @param string|string[] $host
     *
     * @return Page[]
     */
    public function findByHost(string|array $host): array
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $this->andHost($queryBuilder, $host);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string|string[] $host
     *                              Return page for sitemap and main Feed (PageController)
     *                              $queryBuilder->getQuery()->getResult();
     */
    public function getIndexablePagesQuery(
        string|array $host,
        string $locale,
        ?int $limit = null
    ): QueryBuilder {
        $queryBuilder = $this->buildPublishedPageQuery('p');
        $queryBuilder = $this->andIndexable($queryBuilder);
        $queryBuilder = $this->andHost($queryBuilder, $host);
        $queryBuilder = $this->andLocale($queryBuilder, $locale);

        $this->andNotRedirection($queryBuilder);

        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder;
    }

    /**
     * Used in admin PageCrudController.
     *
     * @return Page[]
     */
    public function getPagesWithoutParent(): array
    {
        $query = $this->createQueryBuilder('p')
            ->andWhere('p.parentPage is NULL')
            ->orderBy('p.slug', 'DESC')
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Used in admin Media.
     *
     * @return Page[]
     */
    public function getPagesUsingMedia(Media $media): array
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $orx = $queryBuilder->expr()->orX();
        $orx->add($queryBuilder->expr()->eq('p.mainImage', ':idMedia'));
        $orx->add($queryBuilder->expr()->like('p.mainContent', ':nameMedia')); // catch: 'example'
        $orx->add($queryBuilder->expr()->like('p.mainContent', ':apostrophMedia')); // catch: 'example.jpg'
        $orx->add($queryBuilder->expr()->like('p.mainContent', ':quotedMedia')); // catch: "example.jpg'
        $orx->add($queryBuilder->expr()->like('p.mainContent', ':defaultMedia')); // catch: media/default/example.jpg
        $orx->add($queryBuilder->expr()->like('p.mainContent', ':thumbMedia'));

        $query = $queryBuilder->where($orx)
            ->setParameter('idMedia', $media->getId())
            ->setParameter('nameMedia', "%'".$media->getName()."'%")
            ->setParameter('apostrophMedia', "%'".$media->getMedia()."'%")
            ->setParameter('quotedMedia', '%"'.$media->getMedia().'"%')
            ->setParameter('defaultMedia', '/media/default/'.$media->getMedia().'%')
            ->setParameter('thumbMedia', '/media/thumb/'.$media->getMedia().'%')
            ->getQuery();

        return $query->getResult();
    }

    private function getRootAlias(QueryBuilder $queryBuilder): string
    {
        $aliases = $queryBuilder->getRootAliases();

        if (! isset($aliases[0])) {
            throw new RuntimeException('No alias was set before invoking getRootAlias().');
        }

        return $aliases[0];
    }

    /* ~~~~~~~~~~~~~~~ Query Builder Helper ~~~~~~~~~~~~~~~ */

    /**
     * @param array<(string|int), string> $orderBy containing key,direction
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
            if (! method_exists($queryBuilder, $orderByFunc)) {
                throw new LogicException();
            }

            $queryBuilder->$orderByFunc($this->getRootAlias($queryBuilder).'.'.$key, $direction); // @phpstan-ignore-line
        }

        return $queryBuilder;
    }

    /**
     * @param array<(string|int), string> $orderBy
     */
    private function extractDirection(string &$key, array $orderBy): string
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
     * @param string|string[] $host
     */
    public function andHost(QueryBuilder $queryBuilder, string|array $host): QueryBuilder
    {
        if (\in_array($host, ['', []], true)) {
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
        if ('' === $locale) {
            return $queryBuilder;
        }

        if ('0' === $locale) {
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
     * @param int|array<(string|int), int> $limit containing start,max or just max
     */
    protected function limit(QueryBuilder $queryBuilder, array|int $limit): QueryBuilder
    {
        if (\in_array($limit, [0, []], true)) {
            return $queryBuilder;
        }

        if (\is_array($limit)) {
            return $queryBuilder->setFirstResult($limit['start'] ?? $limit[0])->setMaxResults($limit['max'] ?? $limit[1]);
        }

        return $queryBuilder->setMaxResults($limit);
    }

    /**
     * @param string|string[]|null $host
     *
     * @return string[]
     */
    public function getAllTags(array|string|null $host = null): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p.tags');

        if (null !== $host) {
            $this->andHost($queryBuilder, $host);
        }

        /** @var array{tags: string[]}[] */
        $tags = $queryBuilder->getQuery()->getResult();

        $allTags = [];
        foreach ($tags as $entity) {
            $allTags = array_merge($allTags, $entity['tags']);
        }

        return array_values(array_unique($allTags));
    }

    /**
     * @param string|string[]|null $host
     *
     * @return string[]
     */
    public function getPageUriList(array|string|null $host = null): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p.slug');

        if (null !== $host) {
            $this->andHost($queryBuilder, $host);
        }

        /** @var array{slug: string}[] */
        $results = $queryBuilder->getQuery()->getResult();

        $pageUriList = [];
        foreach ($results as $result) {
            $pageUriList[] = '/'.('homepage' === $result['slug'] ? '' : $result['slug']);
        }

        return $pageUriList;
    }
}
