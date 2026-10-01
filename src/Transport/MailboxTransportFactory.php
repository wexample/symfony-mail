<?php

namespace Wexample\SymfonyMail\Transport;

use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Wexample\SymfonyMail\Service\MailboxService;

/**
 * The dispatcher is what renders a TemplatedEmail: the Twig listener runs on
 * the MessageEvent the transport dispatches before keeping the mail.
 */
class MailboxTransportFactory extends AbstractTransportFactory
{
    public const string SCHEME = 'mailbox';

    private const string ENVIRONMENT_PROD = 'prod';

    public function __construct(
        private readonly MailboxService $mailbox,
        private readonly string $environment,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($dispatcher, null, $logger);
    }

    /**
     * @throws LogicException in prod, where a mail kept on disk is a mail its
     *                        recipient never gets
     */
    public function create(Dsn $dsn): TransportInterface
    {
        if (self::ENVIRONMENT_PROD === $this->environment) {
            throw new LogicException('The mailbox:// transport keeps mails on disk instead of sending them, it is refused in prod. Set a real MAILER_DSN.');
        }

        return new MailboxTransport($this->mailbox, $this->dispatcher, $this->logger);
    }

    protected function getSupportedSchemes(): array
    {
        return [self::SCHEME];
    }
}
