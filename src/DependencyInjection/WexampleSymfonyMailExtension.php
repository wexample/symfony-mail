<?php

namespace Wexample\SymfonyMail\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyMailExtension extends AbstractWexampleSymfonyExtension
{
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $this->loadConfig(
            __DIR__,
            $container
        );

        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('wexample_symfony_mail.layout', $config['layout']);
        $container->setParameter('wexample_symfony_mail.text_layout', $config['text_layout']);
        $container->setParameter('wexample_symfony_mail.app_name', $config['app_name']);
        $container->setParameter('wexample_symfony_mail.mailbox.directory', $config['mailbox']['directory']);
    }
}
