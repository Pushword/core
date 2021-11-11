<?php

namespace Pushword\Core\Component\EntityFilter;

use Pushword\Core\Entity\SharedTrait\IdInterface;

interface ManagerPoolInterface
{
    /** @return mixed */
    public function getProperty(IdInterface $id, string $property = '');

    public function getManager(IdInterface $id): Manager;
}
