<?php

namespace Wexample\SymfonyMail\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_mail');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('mailbox')
                    ->info('Where MAILER_DSN=mailbox://default keeps the mails, instead of sending them. Refused in prod.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('directory')
                            ->defaultValue('%kernel.project_dir%/var/mailbox')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
