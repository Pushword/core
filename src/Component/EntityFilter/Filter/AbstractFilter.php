<?php

namespace Pushword\Core\Component\EntityFilter\Filter;

abstract class AbstractFilter implements FilterInterface
{
    protected function scalar(mixed $value): bool|float|int|string|null
    {
        if (null !== $value && ! \is_scalar($value)) {
            throw new \LogicException(\gettype($value));
        }

        return $value;
    }

    protected function string(mixed $value): string
    {
        return \strval($this->scalar($value));
    }
}
