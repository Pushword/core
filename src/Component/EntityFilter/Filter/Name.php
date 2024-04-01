<?php

namespace Pushword\Core\Component\EntityFilter\Filter;

use Pushword\Core\AutowiringTrait\RequiredManagerTrait;
use Pushword\Core\Component\App\AppConfig;
use Pushword\Core\Component\EntityFilter\Manager;
use Pushword\Core\Entity\Page;

class Name extends AbstractFilter
{
    public AppConfig $app;

    /**
     * @use RequiredManagerTrait<Page>
     */
    public Manager $entityFilterManager;

    public function apply(mixed $propertyValue): ?string
    {
        $names = explode("\n", $this->string($propertyValue));

        return isset($names[0]) && '' !== $names[0] ? trim($names[0])
            : ('' !== $propertyValue ? $propertyValue : $this->entityFilterManager->getH1()); // @phpstan-ignore-line
    }
}
