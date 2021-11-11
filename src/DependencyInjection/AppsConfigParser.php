<?php

namespace Pushword\Core\DependencyInjection;

use LogicException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AppsConfigParser
{
    /**
     * @param array<array<(string|int), array<mixed>>> $apps
     *
     * @return array<array<mixed>>
     */
    public static function parse(array $apps, ContainerBuilder $container): array
    {
        $result = [];
        foreach ($apps as $app) {
            $app = self::parseAppConfig($app, $container);
            $result[$app['hosts'][0]] = $app; // @phpstan-ignore-line
        }

        return $result;
    }

    /**
     * @param array<(string|int), array<mixed>> $app
     *
     * @return array<mixed>
     */
    private static function parseAppConfig(array $app, ContainerBuilder $container): array
    {
        $properties = $container->getParameter('pw.app_fallback_properties');
        if (\is_string($properties)) { // @phpstan-ignore-line
            $properties = explode(',', $properties);
        }

        foreach ($properties as $p) {
            if (! isset($app[$p])) {
                $app[$p] = $container->getParameter('pw.'.$p); //'%'.'pw.'.$p.'%';
            } elseif ('custom_properties' == $p) {
                $app[$p] = array_merge(self::getParameterArray($container, 'pw.'.$p), $app[$p]); // @phpstan-ignore-line
                //var_dump($app[$p]); exit;
            }
        }

        return $app;
    }

    /**
     * @return array<mixed>
     */
    private static function getParameterArray(ContainerBuilder $container, string $parameterName): array
    {
        $return = $container->getParameter($parameterName);
        if (false === \is_array($return)) {
            throw new LogicException('Parameter '.$parameterName.' must be an array');
        }

        return $return;
    }
}
