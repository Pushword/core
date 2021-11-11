<?php

namespace Pushword\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;
use Pushword\Core\Entity\MediaInterface;

/**
 * @extends ServiceEntityRepository<MediaInterface>
 */
class MediaRepository extends ServiceEntityRepository implements ObjectRepository, Selectable
{
    /**
     * @return string[]
     */
    public function getMimeTypes(): array
    {
        $qb = $this->createQueryBuilder('m');
        $qb->select('m.mimeType');
        $qb->groupBy('m.mimeType');
        $qb->orderBy('m.mimeType', Criteria::ASC);

        return array_column($qb->getQuery()->getResult(), 'mimeType');
    }
}
