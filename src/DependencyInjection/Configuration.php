<?php

namespace Connected\Conexteo\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('conexteo');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('dsn')
                    ->defaultNull()
                    ->info('DSN Conexteo. Renseigné, il rend le service ConexteoTransport autowirable.')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
