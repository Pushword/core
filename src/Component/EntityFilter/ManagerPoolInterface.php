<?php

namespace Pushword\Core\Component\EntityFilter;

use Pushword\Core\Entity\SharedTrait\IdInterface;

interface ManagerPoolInterface
{
    /** @return mixed */
    public function getProperty(IdInterface $entity, string $property = '');

    public function getManager(IdInterface $entity): Manager;
}
