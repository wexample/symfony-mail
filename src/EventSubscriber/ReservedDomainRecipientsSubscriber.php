<?php

namespace Wexample\SymfonyMail\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Wexample\SymfonyMail\Transport\MailboxTransportFactory;

/**
 * Never hands a real transport a mail for a domain reserved for examples and
 * tests (RFC 2606, RFC 6761): `example.com`, `*.test`, `*.invalid`… Nobody
 * receives there, and a provider asked to deliver either bounces the mail,
 * which costs the sender its reputation, or refuses it on the spot, which
 * fails the request sending it — a demo account signing in.
 *
 * Those recipients are taken out of the envelope; a mail left with none is
 * not sent. A transport that delivers nothing is left alone: the development
 * mailbox keeps them all — reading such mails is what it is for —, and
 * `null://`, which tests count mails on, sends nothing anyway.
 */
class ReservedDomainRecipientsSubscriber implements EventSubscriberInterface
{
    private const array NOT_DELIVERING_SCHEMES = [MailboxTransportFactory::SCHEME, 'null'];

    private const array RESERVED_DOMAINS = ['example', 'example.com', 'example.net', 'example.org', 'test', 'invalid', 'localhost'];

    public static function getSubscribedEvents(): array
    {
        return [MessageEvent::class => 'onMessage'];
    }

    public function onMessage(MessageEvent $event): void
    {
        foreach (self::NOT_DELIVERING_SCHEMES as $scheme) {
            if (str_starts_with($event->getTransport(), $scheme . '://')) {
                return;
            }
        }

        $envelope = $event->getEnvelope();
        $recipients = array_values(array_filter(
            $envelope->getRecipients(),
            static fn (Address $address): bool => ! self::isReserved($address)
        ));

        if ([] === $recipients) {
            $event->reject();

            return;
        }

        $envelope->setRecipients($recipients);
    }

    public static function isReserved(Address $address): bool
    {
        $domain = strtolower(substr(strrchr($address->getAddress(), '@') ?: '', 1));

        foreach (self::RESERVED_DOMAINS as $reserved) {
            if ($domain === $reserved || str_ends_with($domain, '.' . $reserved)) {
                return true;
            }
        }

        return false;
    }
}
