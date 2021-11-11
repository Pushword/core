<?php

namespace Pushword\Core\DependencyInjection;

use InvalidArgumentException;
use LogicException;
use Pushword\Core\Utils\IsAssociativeArray;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PushwordConfigFactory
{
    private ContainerBuilder $container;

    private string $prefix;

    /** @var array<mixed> */
    private array $config;

    private ?ConfigurationInterface $configuration;

    /** @param array<mixed> $configs */
    public function __construct(
        ContainerBuilder $container,
        array $configs,
        ?ConfigurationInterface $configuration = null,
        string $prefix = ''
    ) {
        $this->container = $container;
        $this->config = $configs;
        $this->prefix = 'pw.'.('' !== $prefix ? $prefix.'.' : '');
        $this->configuration = $configuration;
    }

    public function loadConfigToParams(): self
    {
        $this->loadToParameters($this->config, $this->prefix);

        return $this;
    }

    /**
     * @return array<string>
     */
    private function getAppFallbackConfig(): array
    {
        if (! isset($this->config['app_fallback_properties'])) {
            return [];
        }

        if (\is_string($this->config['app_fallback_properties'])) {
            $this->config['app_fallback_properties'] = explode(',', $this->config['app_fallback_properties']);
        }

        return $this->config['app_fallback_properties'];  // @phpstan-ignore-line
    }

    /**
     * load Apps config and retrieve fallback directly, no need to call processAppsConfiguration.
     */
    public function loadApps(): self
    {
        if (! isset($this->config['apps'])) {
            $this->setParameter('pw.apps', $this->parseApps([]));

            return $this;
        }

        if ($this->container->hasParameter('pw.apps')) { // @phpstan-ignore-line
            throw new InvalidArgumentException('Invalid "apps" name: parameter is ever registered.');
        }

        $this->setParameter('pw.apps', $this->parseApps($this->config['apps'])); // @phpstan-ignore-line

        return $this;
    }

    public function processAppsConfiguration(): void
    {
        if ([] === $this->getAppFallbackConfig()) {
            return;
        }

        if (! $this->container->hasParameter('pw.apps')) { // @phpstan-ignore-line
            throw new LogicException('You must register Pushword/CoreBundle in first (`pw.apps` is not loaded in ParameterBag.');
        }
        $apps = $this->container->getParameter('pw.apps');

        foreach ($apps as $host => $app) {
            $apps[$host] = $this->processAppConfig($app);
        }

        $this->container->setParameter('pw.apps', $apps);
    }

    /**
     * @param array<array<mixed>> $apps
     *
     * @return array<mixed>
     */
    private function parseApps(array $apps): array
    {
        $result = [];
        foreach ($apps as $app) {
            $app = $this->processAppConfig($app);
            if (! isset($app['hosts']) || ! \is_array($app['hosts']) || ! isset($app['hosts'][0])) { // normally, it's impossible to reach this
                throw new InvalidArgumentException('Something is badly configured in your pushword configuration file.');
            }
            $result[$app['hosts'][0]] = $app;
        }

        return $result;
    }

    /**
     * @param array<mixed> $app
     *
     * @return array<mixed>
     */
    private function processAppConfig(array $app): array
    {
        $fallbackProperties = $this->getAppFallbackConfig();

        if (null !== $this->configuration) {
            $configTree = $this->configuration->getConfigTreeBuilder()->buildTree();
            $configTree->finalize($app); // it will check value
        }

        foreach ($fallbackProperties as $p) {
            if (! isset($app[$p])) {
                $app[$p] = ! \is_string($this->config[$p]) ? $this->config[$p]
                    : str_replace('%main_host%', $app['hosts'][0], $this->config[$p]); // @phpstan-ignore-line
            } elseif ('custom_properties' == $p) {
                $app['custom_properties'] = array_merge($this->config['custom_properties'], $app['custom_properties']); // @phpstan-ignore-line
            }
        }

        return $app;
    }

    /**
     * @param array<mixed> $config
     */
    private function loadToParameters(array $config, string $prefix = ''): void
    {
        $fallbackProperties = $this->getAppFallbackConfig();

        foreach ($config as $key => $value) {
            if ('apps' === $key) {
                continue; // We don't process Apps this way
            }

            if (\in_array($key, $fallbackProperties, true)) {
                continue; // We don't load configuration we use in App
            }

            if (\is_array($value)
            && ! \in_array($key, ['image_filter_sets'], true)
                && IsAssociativeArray::test($value)
                ) {
                $this->loadToParameters($value, $prefix.$key.'.');

                continue;
            }

            $this->setParameter($prefix.$key, $value); // @phpstan-ignore-line
        }
    }

    /**
     * @param array<mixed>|bool|string|int|float|null $value The parameter value
     */
    private function setParameter(string $key, $value): void
    {
        if ($this->container->hasParameter($key)) {
            throw new InvalidArgumentException(\Safe\sprintf('Invalid "%s" name: parameter is ever registered.', $key));
        }

        $this->container->setParameter($key, $value);
    }
}
