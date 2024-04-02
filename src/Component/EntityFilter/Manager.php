<?php

namespace Pushword\Core\Component\EntityFilter;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use Pushword\Core\Component\App\AppConfig;
use Pushword\Core\Component\App\AppPool;
use Pushword\Core\Component\EntityFilter\Filter\FilterInterface;
use Pushword\Core\Entity\Page;
use Pushword\Core\Router\PushwordRouteGenerator;
use Pushword\Core\Service\LinkProvider;
use ReflectionClass;

use function Safe\preg_match;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment as Twig;

final readonly class Manager
{
    private AppConfig $app;

    private AppPool $apps;

    private Twig $twig;

    private PushwordRouteGenerator $router;

    private EntityManagerInterface $entityManager;

    public function __construct(
        private ManagerPool $managerPool,
        private EventDispatcherInterface $eventDispatcher,
        private LinkProvider $linkProvider,
        public readonly Page $page
    ) {
        $this->apps = $managerPool->apps;
        $this->twig = $managerPool->twig;
        $this->router = $managerPool->router;
        $this->entityManager = $managerPool->entityManager;
        $this->app = $this->apps->get($page->getHost());
    }

    public function getPage(): Page
    {
        return $this->page;
    }

    /**
     * Magic getter for Entity properties.
     *
     * @param array<mixed> $arguments
     */
    public function __call(string $method, array $arguments = []): mixed
    {
        if (preg_match('/^get/', $method) < 1) {
            $method = 'get'.ucfirst($method);
        }

        $filterEvent = new FilterEvent($this, substr($method, 3));
        $this->eventDispatcher->dispatch($filterEvent, FilterEvent::NAME_BEFORE);

        if (! \is_callable($pageMethod = [$this->page, $method])) {
            throw new LogicException();
        }

        $returnValue = [] !== $arguments ? \call_user_func_array($pageMethod, $arguments) : \call_user_func($pageMethod);

        if (! \is_scalar($returnValue)) {
            throw new LogicException();
        }

        $returnValue = $this->filter(substr($method, 3), $returnValue);

        $this->eventDispatcher->dispatch($filterEvent, FilterEvent::NAME_AFTER);

        return $returnValue;
    }

    /**
     * main_content => apply filters on mainContent (*_filters => camelCase(*))
     * string       => apply filters on each string property.
     */
    private function filter(string $property, bool|float|int|string|null $propertyValue): mixed
    {
        $filters = $this->getFilters($this->camelCaseToSnakeCase($property));

        if ([] === $filters && \is_string($propertyValue)) {
            $filters = $this->getFilters('string');
        }

        return [] !== $filters
            ? $this->applyFilters($property,  '' !== \strval($propertyValue) ? $propertyValue : '', $filters)
            : $propertyValue;
    }

    private function camelCaseToSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($string)) ?? throw new Exception());
    }

    /**
     * @return string[]
     *
     * @psalm-suppress all
     */
    private function getFilters(string $label): array
    {
        if ($this->app->entityCanOverrideFilters()) {
            $filters = $this->page->getCustomProperty($label.'_filters');
        }

        if (! isset($filters) || \is_string($filters) && \in_array($filters, [[], '', null], true)) {
            $appFilters = $this->app->getFilters();
            $filters = $appFilters[$label] ?? null;
        }

        return \is_string($filters) ? explode(',', $filters) : (\is_array($filters) ? $filters : []);
    }

    /**
     * @noRector
     *
     * @return false|class-string
     */
    private function isFilter(string $className): false|string
    {
        $filterClass = class_exists($className) ? $className
            : 'Pushword\Core\Component\EntityFilter\Filter\\'.ucfirst($className);

        if (! class_exists($filterClass)) {
            return false;
        }

        $reflectionClass = new ReflectionClass($filterClass);
        if (! $reflectionClass->implementsInterface(FilterInterface::class)) {
            return false;
        }

        return $filterClass;
    }

    private function getFilterClass(string $filter): FilterInterface
    {
        if (false === ($filterClassName = $this->isFilter($filter))) {
            throw new Exception('Filter `'.$filter.'` not found');
        }

        /** @var class-string<FilterInterface> $filterClassName */
        $filterClass = new $filterClassName();

        $toAutowire = [
            'page', 'app', 'apps', 'twig', 'entityFilterManager', 'managerPool', 'router', 'entityManager', 'linkProvider',
        ];

        foreach ($toAutowire as $property) {
            if (property_exists($filterClass, $property)) {
                $filterClass->$property = 'entityFilterManager' === $property ? $this : $this->$property; // @phpstan-ignore-line
            }
        }

        return $filterClass;
    }

    /**
     * @param string[] $filters
     */
    private function applyFilters(string $property, bool|float|int|string|null $propertyValue, array $filters): mixed
    {
        foreach ($filters as $filter) {
            if (\in_array($this->page->getCustomProperty('filter_'.$this->className($filter)), [0, false], true)) {
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
