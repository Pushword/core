<?php

namespace Pushword\Core\Component\EntityFilter\Filter;

use Pushword\Core\AutowiringTrait\RequiredEntityTrait;
use Pushword\Core\AutowiringTrait\RequiredTwigTrait;
use Pushword\Core\Entity\PageInterface;

class Twig extends AbstractFilter
{
    use RequiredEntityTrait;
    use RequiredTwigTrait;

    /**
     * @return string
     */
    public function apply($propertyValue)
    {
        return $this->render(\strval($propertyValue));
    }

    protected function render(string $string): string
    {
        if (false === strpos($string, '{')) {
            return $string;
        }

        $templateWrapper = $this->twig->createTemplate($string);

        return $templateWrapper->render($this->entity instanceof PageInterface ? ['page' => $this->entity] : []);
    }
}
