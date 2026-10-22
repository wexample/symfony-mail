<?php

namespace Wexample\SymfonyMail\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Wexample\SymfonyMail\Service\MailboxService;

/**
 * `MAILER_DSN=mailbox://default`: every mail lands in the mailbox directory
 * instead of leaving the machine.
 */
class MailboxTransport extends AbstractTransport
{
    public function __construct(
        private readonly MailboxService $mailbox,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($dispatcher, $logger);
    }

    protected function doSend(SentMessage $message): void
    {
        $this->mailbox->store($message);
    }

    public function __toString(): string
    {
        return MailboxTransportFactory::SCHEME.'://'.$this->mailbox->getDirectory();
    }
}
