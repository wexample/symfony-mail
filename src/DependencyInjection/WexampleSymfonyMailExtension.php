<?php

namespace Wexample\SymfonyMail\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyMailExtension extends AbstractWexampleSymfonyExtension
{
    final public const string PARAMETER_SENDER = 'wexample_symfony_mail.sender';
    final public const string PARAMETER_SENDER_ENV = 'wexample_symfony_mail.sender_env';

    /**
     * Who the mails leave from, read from the application's `framework.mailer`
     * before the container resolves it: whether `headers` names a From or a
     * Sender, and the variable it is read from when it is `%env(NAME)%` — a
     * variable left unset would be a sender the configuration promised.
     * MailRequirementProvider says what is missing.
     */
    public function prepend(ContainerBuilder $container): void
    {
        parent::prepend($container);

        $sender = null;
        foreach ($container->getExtensionConfig('framework') as $config) {
            foreach ($config['mailer']['headers'] ?? [] as $name => $value) {
                // Given as a map, or as a list of { name, value }.
                if (is_array($value)) {
                    $name = $value['name'] ?? $name;
                    $value = $value['value'] ?? null;
                }

                if (in_array(strtolower((string) $name), ['from', 'sender'], true) && is_string($value) && trim($value) !== '') {
                    $sender = $value;
                }
            }
        }

        $container->setParameter(self::PARAMETER_SENDER, $sender !== null);
        $container->setParameter(
            self::PARAMETER_SENDER_ENV,
            $sender !== null && preg_match('/^%env\((?:[a-z]+:)*([A-Z0-9_]+)\)%$/', $sender, $match) ? $match[1] : null
        );
    }

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
