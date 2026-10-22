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
                ->scalarNode('layout')
                    ->info('The frame every HTML mail is drawn in. An application gives its own, which may extend this one and override its blocks.')
                    ->defaultValue('@WexampleSymfonyMailBundle/mails/layout.html.twig')
                ->end()
                ->scalarNode('text_layout')
                    ->info('The text part of every HTML mail: the same template, its links written out.')
                    ->defaultValue('@WexampleSymfonyMailBundle/mails/layout.txt.twig')
                ->end()
                ->scalarNode('app_name')
                    ->info('Named in the header and the footer of the layout.')
                    ->defaultNull()
                ->end()
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
