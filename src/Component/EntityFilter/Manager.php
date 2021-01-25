<?php

namespace Pushword\Core\Component\EntityFilter;

use Exception;
use Pushword\Core\Component\App\AppConfig;
use Pushword\Core\Component\App\AppPool;
use Pushword\Core\Component\EntityFilter\Filter\FilterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment as Twig;

class Manager
{
    private $entity;
    private AppConfig $app;
    private AppPool $apps;
    private Twig $twig;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        AppPool $apps,
        Twig $twig,
        EventDispatcherInterface $eventDispatcher,
        $entity
    ) {
        $this->apps = $apps;
        $this->twig = $twig;
        $this->entity = $entity;
        $this->eventDispatcher = $eventDispatcher;
        $this->app = method_exists($entity, 'getHost') ? $this->apps->get($entity->getHost()) : $apps->get();
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    /**
     * Magic getter for Entity properties.
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments = [])
    {
        if (! preg_match('/^get/', $method)) {
            $method = 'get'.ucfirst($method);
        }

        $returnValue = $arguments ? \call_user_func_array([$this->entity, $method], $arguments)
            : \call_user_func([$this->entity, $method]);

        $returnValue = $this->filter(substr($method, 3), $returnValue);

        $event = new AfterFilterEvent($this, substr($method, 3));
        $this->eventDispatcher->dispatch($event, AfterFilterEvent::NAME);

        return $returnValue;
    }

    /**
     * main_content => apply filters on mainContent (*_filters => camelCase(*))
     * string       => apply filters on each string property.
     *
     * @param mixed $propertyValue
     *
     * @return mixed
     */
    private function filter(string $property, $propertyValue)
    {
        $filters = $this->getFilters($this->camelCaseToSnakeCase($property));

        if (! $filters && \is_string($propertyValue)) {
            $filters = $this->getFilters('string');
        }

        return $filters ? $this->applyFilters($propertyValue ?: '', $filters) : $propertyValue;
    }

    private function camelCaseToSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($string)));
    }

    private function getFilters(string $label): ?array
    {
        if ($this->app->entityCanOverrideFilters()) {
            $filters = $this->entity->getCustomProperty($label);
        }

        if (! isset($filters) || ! $filters) {
            $appFilters = $this->app->getFilters();
            $filters = isset($appFilters[$label]) ? $appFilters[$label] : null;
        }

        $filters = \is_string($filters) ? explode(',', $filters) : $filters;

        return $filters ?: null;
    }

    private function getFilterClass($filter): FilterInterface
    {
        $filterClass = $filter;
        if (! class_exists($filterClass)) {
            $filterClass = 'Pushword\Core\Component\EntityFilter\Filter\\'.ucfirst($filter);
            if (! class_exists($filterClass)) {
                throw new Exception('Filter `'.$filter.'` not found');
            }
        }

        $filterClass = new $filterClass();

        if (method_exists($filterClass, 'setEntity')) {
            $filterClass->setEntity($this->entity);
        }

        if (method_exists($filterClass, 'setApp')) {
            $filterClass->setApp($this->app);
        }

        if (method_exists($filterClass, 'setTwig')) {
            $filterClass->setTwig($this->twig);
        }

        if (method_exists($filterClass, 'setManager')) {
            $filterClass->setManager($this);
        }

        // todo autowire
        return $filterClass;
    }

    private function applyFilters($propertyValue, array $filters)
    {
        foreach ($filters as $filter) {
            if (\in_array($this->entity->getCustomProperty('filter_'.$this->className($filter)), [0, false], true)) {
                continue;
            }
            $filterClass = $this->getFilterClass($filter);

            $propertyValue = $filterClass->apply($propertyValue);
        }

        return $propertyValue;
    }

    private function className($name)
    {
        $name = substr($name, strrpos($name, '/'));

        return lcfirst($name);
    }
}
