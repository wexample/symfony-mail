<?php

namespace Wexample\SymfonyMail\Tests\Fixtures\Transport;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * A mail server that takes every mail and keeps nothing: one that delivers,
 * as far as the application can tell.
 */
class DeliveringTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
    }

    public function __toString(): string
    {
        return 'smtp://mail.example.net';
    }
}
