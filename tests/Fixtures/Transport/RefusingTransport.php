<?php

namespace Wexample\SymfonyMail\Tests\Fixtures\Transport;

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * A mail server that refuses every mail, once it is rendered.
 */
class RefusingTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        throw new TransportException('550 Mailbox unavailable');
    }

    public function __toString(): string
    {
        return 'refusing://';
    }
}
