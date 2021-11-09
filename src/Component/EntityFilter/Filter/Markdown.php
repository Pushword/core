<?php

namespace Pushword\Core\Component\EntityFilter\Filter;

use Pushword\Core\Utils\MarkdownParser;

class Markdown extends AbstractFilter
{
    /**
     * @return string
     */
    public function apply($propertyValue)
    {
        return $this->render($propertyValue);
    }

    private function render(string $string): string
    {
        return (new MarkdownParser())->transform($string);
    }
}
