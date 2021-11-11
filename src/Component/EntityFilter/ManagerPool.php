<?php

namespace Pushword\Core\Component\EntityFilter;

use Exception;
use Pushword\Core\Component\App\AppPool;
use Pushword\Core\Entity\SharedTrait\IdInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment as Twig;

final class ManagerPool implements ManagerPoolInterface
{
    /** @required */
    public AppPool $apps;

    /** @required */
    public Twig $twig;

    /** @required */
    public EventDispatcherInterface $eventDispatcher;

    /** @var array<(string|int), Manager> */
    private array $entityFilterManagers = [];

    public function getManager(IdInterface $entity): Manager
    {
        if (null !== $entity->getId() && isset($this->entityFilterManagers[$entity->getId()])) {
            return $this->entityFilterManagers[$entity->getId()];
        }

        $this->entityFilterManagers[$entity->getId()] = new Manager($this, $this->eventDispatcher, $entity);

        return $this->entityFilterManagers[$entity->getId()];
    }

    public function getProperty(IdInterface $entity, string $property = '')
    {
        $manager = $this->getManager($entity);

        if ('' === $property) {
            return $manager;
        }

        if (! method_exists($manager, $property)) {
            throw new Exception('Property `'.$property.'` doesn\'t exist');
        }

        return $manager->$property(); // @phpstan-ignore-line
    }
}
