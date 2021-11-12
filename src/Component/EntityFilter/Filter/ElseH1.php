<?php

namespace Pushword\Core\Component\EntityFilter\Filter;

use Pushword\Core\AutowiringTrait\RequiredAppTrait;
use Pushword\Core\AutowiringTrait\RequiredManagerTrait;
use Pushword\Core\Entity\PageInterface;

class ElseH1 extends AbstractFilter
{
    use RequiredAppTrait;
    /*
     * @use RequiredManagerTrait<PageInterface>
     */
    use RequiredManagerTrait;

    /** @return ?string */
    public function apply($propertyValue): ?string
    {
        return '' !== \strval($propertyValue) ? $propertyValue : $this->entityFilterManager->getEntity()->getH1();
    }
}
