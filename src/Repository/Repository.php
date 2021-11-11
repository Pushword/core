<?php

namespace Pushword\Core\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

class Repository
{
    /**
     * @param ManagerRegistry|EntityManager $doctrine
     */
    public static function getPageRepository($doctrine, string $pageEntity): PageRepositoryInterface
    {
        return $doctrine->getRepository($pageEntity);
    }

    /**
     * @param ManagerRegistry|EntityManager $doctrine
     */
    public static function getMediaRepository($doctrine, string $pageEntity): MediaRepository
    {
        return $doctrine->getRepository($pageEntity);
    }
}
