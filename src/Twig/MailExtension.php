<?php

namespace Wexample\SymfonyMail\Twig;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Wexample\SymfonyHelpers\Twig\AbstractExtension;
use Wexample\SymfonyMail\Helper\MailTextHelper;

class MailExtension extends AbstractExtension
{
    /**
     * The mailbox page of symfony-mail-ds, routed in dev and test only.
     */
    public const string ROUTE_MAILBOX = 'mailbox_index';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'mail_text',
                [
                    MailTextHelper::class,
                    'fromHtml',
                ]
            ),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'mailbox_url',
                [
                    $this,
                    'mailboxUrl',
                ]
            ),
        ];
    }

    /**
     * Where a page that has just sent a mail sends its reader in development.
     *
     * @param string|null $recipient the mailbox opens on this address's mails
     * @return string|null null where the mailbox is not routed: in prod, or
     *                     without symfony-mail-ds
     */
    public function mailboxUrl(?string $recipient = null): ?string
    {
        try {
            return $this->urlGenerator->generate(
                self::ROUTE_MAILBOX,
                null !== $recipient ? ['to' => $recipient] : []
            );
        } catch (RouteNotFoundException) {
            return null;
        }
    }
}
