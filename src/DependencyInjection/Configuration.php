<?php

namespace Pushword\Core\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_TEMPLATE = '@Pushword';
    const DEFAULT_APP_FALLBACK = [
        'hosts',
        'locale',
        'locales',
        'name',
        'base_url',
        'template',
        'template_dir',
        'custom_properties',
    ];
    const DEFAULT_CUSTOM_PROPERTIES = [
        'main_content_type' => 'Raw', // not anymore used, replaced by filters... to remove
        'can_use_twig_shortcode' => true,
        'main_content_shortcode' => 'twig,date,email,encryptedLink,image,phoneNumber,twigVideo,punctuation,markdown,unprose',
        'fields_shortcode' => 'twig,date,email,encryptedLink,phoneNumber',
        'assets' => [
            'stylesheets' => [
                '/bundles/pushwordcore/tailwind.css',
            ],
            'javascripts' => ['/bundles/pushwordcore/page.js'],
        ],
    ];
    const DEFAULT_TWIG_SHORTCODE = true;
    const DEFAULT_PUBLIC_MEDIA_DIR = '/media';
    const IMAGE_FILTERS_SET = [
        'default' => ['quality' => 90, 'filters' => ['downscale' => [1980, 1280]]],
        'thumb' => [
            'quality' => 80,
            'filters' => [
                'fit' => [
                    330,
                    330,
                ],
            ],
        ],
        'height_300' => [
            'quality' => 82,
            'filters' => [
                'heighten_notupsize' => 300,
            ],
        ],
        'xs' => [
            'quality' => 85,
            'filters' => [
                'widen_notupsize' => 576,
            ],
        ],
        'sm' => [
            'quality' => 85,
            'filters' => [
                'widen_notupsize' => 768,
            ],
        ],
        'md' => [
            'quality' => 85,
            'filters' => [
                'widen_notupsize' => 992,
            ],
        ],
        'lg' => [
            'quality' => 85,
            'filters' => [
                'widen_notupsize' => 1200,
            ],
        ],
        'xl' => [
            'quality' => 85,
            'filters' => [
                'widen' => [
                    1600,
                    'constraint' => '$constraint->upsize();',
                ],
            ],
        ],
    ];

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('pushword');
        $treeBuilder->getRootNode()->children()
            ->scalarNode('public_dir')->defaultValue('%kernel.project_dir%/public')->cannotBeEmpty()->end()
            ->scalarNode('entity_page')->defaultValue('App\Entity\Page')->cannotBeEmpty()->end()
            ->scalarNode('entity_media')->defaultValue('App\Entity\Media')->cannotBeEmpty()->end()
            ->scalarNode('entity_user')->defaultValue('App\Entity\User')->cannotBeEmpty()->end()
            ->scalarNode('entity_pagehasmedia')->defaultValue('App\Entity\PageHasMedia')->cannotBeEmpty()->end()
            ->scalarNode('media_dir')
                ->defaultValue('%kernel.project_dir%/media')->cannotBeEmpty()
                ->info('Dir where files will be uploaded when using admin.')
                ->end()
            ->scalarNode('public_media_dir')
                ->defaultValue(self::DEFAULT_PUBLIC_MEDIA_DIR)->cannotBeEmpty()
                ->info('Used to generate browser path. Must be accessible from public_dir.')
                ->end()
            ->variableNode('app_fallback_properties')->defaultValue(self::DEFAULT_APP_FALLBACK)->cannotBeEmpty()->end()
            // default app value
            ->scalarNode('locale')->defaultValue('%locale%')->cannotBeEmpty()->end()
            ->scalarNode('locales')
                ->info('eg: fr|en')
                ->defaultValue('%locale%')
                ->end()
            ->scalarNode('name')->defaultValue('Pushword')->end()
            ->variableNode('host')->defaultValue('localhost')->end()
            ->variableNode('hosts')->defaultValue(['%pw.host%'])->end()
            ->scalarNode('base_url')->defaultValue('https://%pw.host%')->end()
            ->scalarNode('image_filter_sets')->defaultValue(self::IMAGE_FILTERS_SET)->cannotBeEmpty()->end()
            ->scalarNode('template')->defaultValue(self::DEFAULT_TEMPLATE)->cannotBeEmpty()->end()
            ->scalarNode('template_dir')->defaultValue('%kernel.project_dir%/templates')->cannotBeEmpty()->end()
            // The following is a garbage, useful for quick new extension not well designed (no check for conf values)
            ->variableNode('custom_properties')->defaultValue(self::DEFAULT_CUSTOM_PROPERTIES)->end()

            ->variableNode('apps')->defaultValue([[]])->end()
        ->end();

        return $treeBuilder;
    }
}
