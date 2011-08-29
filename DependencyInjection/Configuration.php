<?php

namespace Heri\WebServiceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

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
        $rootNode = $treeBuilder->root('heri_web_service');
        
        $this->addNamespaceSection($rootNode);
        $this->addAuthenticationSection($rootNode);
        $this->addSoapUrlSection($rootNode);

        return $treeBuilder;
    }
    
    private function addNamespaceSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('namespaces')
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;
    }
    
    private function addSoapUrlSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('webservices')
                    ->prototype('array')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('url')->isRequired()->end()
                            ->booleanNode('authentication')->defaultFalse()->end()
                            ->booleanNode('cache_enabled')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
    
    private function addAuthenticationSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('authentication')
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('login')->isRequired()->end()
                        ->scalarNode('password')->isRequired()->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
