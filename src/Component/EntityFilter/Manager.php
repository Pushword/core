<?php

namespace Pushword\Core\Component\EntityFilter;

use Exception;
use Pushword\Core\Component\App\AppConfig;
use Pushword\Core\Component\App\AppPool;
use Pushword\Core\Component\EntityFilter\Filter\FilterInterface;
use Pushword\Core\Entity\SharedTrait\CustomPropertiesInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment as Twig;

final class Manager
{
    /** @var object */
    private $entity;

    private AppConfig $app;

    private AppPool $apps;

    private ManagerPool $managerPool;

    private Twig $twig;

    private EventDispatcherInterface $eventDispatcher;

    /** @param object $entity */
    public function __construct(
        ManagerPool $managerPool,
        EventDispatcherInterface $eventDispatcher,
        object $entity
    ) {
        $this->managerPool = $managerPool;
        $this->apps = $managerPool->apps;
        $this->twig = $managerPool->twig;
        $this->entity = $entity;
        $this->eventDispatcher = $eventDispatcher;
        $this->app = method_exists($entity, 'getHost') ? $this->apps->get($entity->getHost()) : $this->apps->get();
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    /**
     * Magic getter for Entity properties.
     *
     * @param array<mixed> $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments = [])
    {
        if (preg_match('/^get/', $method) < 1) {
            $method = 'get'.ucfirst($method);
        }

        $event = new FilterEvent($this, substr($method, 3));
        $this->eventDispatcher->dispatch($event, FilterEvent::NAME_BEFORE);

        $returnValue = [] !== $arguments ? \call_user_func_array([$this->entity, $method], $arguments)
            : \call_user_func([$this->entity, $method]);

        $returnValue = $this->filter(substr($method, 3), $returnValue);

        $this->eventDispatcher->dispatch($event, FilterEvent::NAME_AFTER);

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

        if (null === $filters && \is_string($propertyValue)) {
            $filters = $this->getFilters('string');
        }

        return null !== $filters
            ? $this->applyFilters($property,  '' !== \strval($propertyValue) ? $propertyValue : '', $filters)
            : $propertyValue;
    }

    private function camelCaseToSnakeCase(string $string): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '_\\0', lcfirst($string)));
    }

    /** @return string[] */
    private function getFilters(string $label): ?array
    {
        if ($this->app->entityCanOverrideFilters() && $this->entity instanceof CustomPropertiesInterface) {
            $filters = $this->entity->getCustomProperty($label.'_filters');
        }

        if (! isset($filters) || ! $filters) {
            $appFilters = $this->app->getFilters();
            $filters = isset($appFilters[$label]) ? $appFilters[$label] : null;
        }

        $filters = \is_string($filters) ? explode(',', $filters) : $filters;

        return $filters ? $filters : null;
    }

    private function getFilterClass(string $filter): FilterInterface
    {
        $filterClass = $filter;
        if (! class_exists($filterClass)) {
            $filterClass = 'Pushword\Core\Component\EntityFilter\Filter\\'.ucfirst($filter);
            if (! class_exists($filterClass)) {
                throw new Exception('Filter `'.$filter.'` not found');
            }
        }

        $filterClass = new $filterClass();

        // Some kind of autoload ... move it to real autoload
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

        if (method_exists($filterClass, 'setManagerPool')) {
            $filterClass->setManagerPool($this->managerPool);
        }

        return $filterClass;
    }

    /**
     * @param mixed $propertyValue
     *
     * @return mixed
     */
    private function applyFilters(string $property, $propertyValue, array $filters)
    {
        foreach ($filters as $filter) {
            if ($this->entity instanceof CustomPropertiesInterface
                && \in_array($this->entity->getCustomProperty('filter_'.$this->className($filter)), [0, false], true)) {
                continue;
            }
            $filterClass = $this->getFilterClass($filter);

            if (method_exists($filterClass, 'setProperty')) {
                $filterClass->setProperty($property);
            }

            $propertyValue = $filterClass->apply($propertyValue);
        }

        return $propertyValue;
    }

    private function className(string $name): string
    {
        $name = substr($name, (int) strrpos($name, '/'));

        return lcfirst($name);
    }
}
