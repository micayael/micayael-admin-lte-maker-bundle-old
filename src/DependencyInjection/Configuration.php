<?php

namespace Micayael\AdminLteMakerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('micayael_admin_lte_maker');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('url_context')
                    ->defaultValue('/admin/')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
