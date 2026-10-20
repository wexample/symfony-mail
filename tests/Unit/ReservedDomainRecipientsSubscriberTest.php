<?php

namespace Wexample\SymfonyMail\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Wexample\SymfonyMail\EventSubscriber\ReservedDomainRecipientsSubscriber;

class ReservedDomainRecipientsSubscriberTest extends TestCase
{
    private function dispatch(string $transport, string ...$recipients): MessageEvent
    {
        $event = new MessageEvent(
            (new Email())->text('Hello'),
            new Envelope(new Address('noreply@weeger.io'), array_map(static fn (string $address) => new Address($address), $recipients)),
            $transport
        );

        (new ReservedDomainRecipientsSubscriber())->onMessage($event);

        return $event;
    }

    public function testAMailForReservedDomainsOnlyIsNotSent(): void
    {
        $this->assertTrue($this->dispatch('smtp://mail.weeger.io', 'active@example.com', 'a@b.test')->isRejected());
    }

    public function testTheReservedRecipientsAreTakenOut(): void
    {
        $event = $this->dispatch('smtp://mail.weeger.io', 'active@example.com', 'romain@weeger.io', 'x@sub.example.org');

        $this->assertFalse($event->isRejected());
        $this->assertSame(['romain@weeger.io'], array_map(static fn (Address $address) => $address->getAddress(), $event->getEnvelope()->getRecipients()));
    }

    public function testANameMerelyEndingLikeAReservedOneIsKept(): void
    {
        $this->assertFalse($this->dispatch('smtp://mail.weeger.io', 'someone@myexample.com', 'a@contest')->isRejected());
    }

    public function testTheMailboxKeepsThemAll(): void
    {
        $event = $this->dispatch('mailbox:///var/mailbox', 'active@example.com');

        $this->assertFalse($event->isRejected());
        $this->assertCount(1, $event->getEnvelope()->getRecipients());
    }
}
