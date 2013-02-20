<?php

namespace Gorg\Bundle\ReplicationTriggerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('gorg_replication_trigger');
        $rootNode
            ->children()
                ->arrayNode('pdo_connections')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('dsn')->end()
                        ->scalarNode('user')->end()
                        ->scalarNode('password')->end()
                    ->end()
                    ->end()
                ->end()
                ->arrayNode('trigger')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('type')->end()
                        ->scalarNode('entityManager')->end()
                        ->scalarNode('event')->end()
                        ->scalarNode('completer')->end()
                        ->arrayNode('config')
                            ->children()
                                ->scalarNode('fetch')->end()
                                ->scalarNode('new')->end()
                                ->scalarNode('update')->end()
                                ->scalarNode('remove')->end()
                                ->scalarNode('class')->end()
                                ->scalarNode('key')->end()
                                ->scalarNode('arrayName')->end()
                                ->arrayNode('target')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('mapping')
                                    ->prototype('scalar')->end()
                                ->end()
                                ->arrayNode('extra')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
