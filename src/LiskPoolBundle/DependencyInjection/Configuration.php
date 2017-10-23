<?php
namespace LiskPoolBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('lisk_pool');

        $rootNode
            ->children()
                ->scalarNode('delegate_username')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('memcached')
                    ->children()
                        ->scalarNode('host')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->integerNode('port')
                            ->isRequired()
                            ->min(1)
                            ->max(65535)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('forging')
                    ->children()
                        ->arrayNode('nodes')
                            ->prototype('scalar')
                            ->end()
                        ->end()
                        ->scalarNode('secret')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('second_secret')
                            ->isRequired()
                        ->end()
                        ->scalarNode('public_key')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->integerNode('fee_in_percentage')
                            ->isRequired()
                        ->end()
                        ->integerNode('minimum_payout')
                            ->isRequired()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
