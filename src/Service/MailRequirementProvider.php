<?php

namespace Wexample\SymfonyMail\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Wexample\SymfonyLoader\Class\Requirement;
use Wexample\SymfonyLoader\Interface\RequirementProviderInterface;
use Wexample\SymfonyMail\DependencyInjection\WexampleSymfonyMailExtension;

/**
 * What a mail needs to leave: a sender for every mail, which MailSenderService
 * leaves to the mailer (`framework.mailer.headers.From`) — without it the
 * transport refuses each one, in a worker, long after the configuration was
 * written.
 */
class MailRequirementProvider implements RequirementProviderInterface
{
    public function __construct(
        #[Autowire(param: WexampleSymfonyMailExtension::PARAMETER_SENDER)]
        private readonly bool $sender,
        #[Autowire(param: WexampleSymfonyMailExtension::PARAMETER_SENDER_ENV)]
        private readonly ?string $senderEnv,
    ) {
    }

    public function getRequirements(): iterable
    {
        yield new Requirement(
            'framework.mailer.headers.From',
            $this->sender,
            'Name who the mails are from in config/packages/mailer.yaml: headers: { From: \'%env(MAILER_FROM)%\' }.'
        );

        if ($this->senderEnv !== null) {
            yield Requirement::env($this->senderEnv, 'Set it to the sender address, \'App <noreply@example.com>\'.');
        }
    }
}
