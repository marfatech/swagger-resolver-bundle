<?php

declare(strict_types=1);

/*
 * This file is part of the SwaggerResolverBundle package.
 *
 * (c) Viktor Linkin <adrenalinkin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Linkin\Bundle\SwaggerResolverBundle\DependencyInjection;

use Linkin\Bundle\SwaggerResolverBundle\Enum\ParameterLocationEnum;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Viktor Linkin <adrenalinkin@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('linkin_swagger_resolver');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('enable_normalization')
                    ->enumPrototype()
                        ->values(ParameterLocationEnum::getAll())
                    ->end()
                    ->defaultValue([
                        ParameterLocationEnum::IN_QUERY,
                        ParameterLocationEnum::IN_PATH,
                        ParameterLocationEnum::IN_HEADER,
                    ])
                ->end()
                ->scalarNode('path_merge_strategy')
                    ->defaultValue('linkin_swagger_resolver.merge_strategy.strict')
                ->end()
                ->arrayNode('configuration_file')
                    ->useAttributeAsKey('area')
                    ->arrayPrototype()
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('file')
                                ->defaultNull()
                                ->info('Serialized OpenAPI configuration in json/yaml format file.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('openapi_annotation')
                    ->useAttributeAsKey('area')
                    ->arrayPrototype()
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('scan')->defaultValue('%kernel.project_dir%/src')->end()
                            ->scalarNode('exclude')->defaultValue([])->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
