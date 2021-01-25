<?php

namespace Pushword\Core\Component\EntityFilter;

use Pushword\Core\Component\App\AppPool;
use Twig\Environment as Twig;

class ManagerPool implements ManagerPoolInterface
{
    /** @required */
    public AppPool $apps;

    /** @required */
    public Twig $twig;

    private array $entityFilterManagers = [];

    public function getManager(object $entity): Manager
    {
        if ($entity->getId() && isset($this->entityFilterManagers[$entity->getId()])) {
            return $this->entityFilterManagers[$entity->getId()];
        }

        $this->entityFilterManagers[$entity->getId()] = new Manager($this->apps, $this->twig, $entity);

        return $this->entityFilterManagers[$entity->getId()];
    }

    public function getProperty(object $entity, string $property = '')
    {
        $manager = $this->getManager($entity);

        if (! $property) {
            return $manager;
        }

        return $manager->$property();
    }
}
