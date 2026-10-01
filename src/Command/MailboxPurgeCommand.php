<?php

namespace Wexample\SymfonyMail\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wexample\SymfonyHelpers\Command\AbstractBundleCommand;
use Wexample\SymfonyHelpers\Service\BundleService;
use Wexample\SymfonyMail\Service\MailboxService;
use Wexample\SymfonyMail\WexampleSymfonyMailBundle;

class MailboxPurgeCommand extends AbstractBundleCommand
{
    public function __construct(
        BundleService $bundleService,
        private readonly MailboxService $mailbox,
    ) {
        parent::__construct($bundleService);
    }

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyMailBundle::class;
    }

    protected function configure(): void
    {
        $this->setDescription('Empties the development mailbox.');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $output->writeln(sprintf('%d mail(s) removed.', $this->mailbox->purge()));

        return self::SUCCESS;
    }
}
