<?php

namespace Pushword\Core\Component\EntityFilter;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * The order.placed event is dispatched each time an order is created
 * in the system.
 */
class AfterFilterEvent extends Event
{
    public const NAME = 'entity_filter.after_filtering';

    private Manager $manager;
    private string $property;

    public function __construct(Manager $manager, string $property)
    {
        $this->manager = $manager;
        $this->property = $property;
    }

    public function getManager(): manager
    {
        return $this->manager;
    }

    public function getProperty(): string
    {
        return $this->property;
    }
}
