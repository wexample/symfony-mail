<?php

namespace Wexample\SymfonyMail\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wexample\SymfonyHelpers\Command\AbstractBundleCommand;
use Wexample\SymfonyHelpers\Service\BundleService;
use Wexample\SymfonyMail\Service\MailboxService;
use Wexample\SymfonyMail\WexampleSymfonyMailBundle;

/**
 * The last mail of the development mailbox, for a script or an agent: with
 * --link, only its first link, so `$(… --link)` can be followed as is.
 */
class MailboxLastCommand extends AbstractBundleCommand
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
        $this
            ->setDescription('Prints the last mail of the development mailbox.')
            ->addArgument('recipient', InputArgument::OPTIONAL, 'The last mail delivered to this address.')
            ->addOption('link', null, InputOption::VALUE_NONE, 'Print its first link only.');
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $mail = $this->mailbox->last($input->getArgument('recipient'));

        if (null === $mail) {
            $output->writeln('<error>No mail.</error>');

            return self::FAILURE;
        }

        if ($input->getOption('link')) {
            if (null === $mail->getFirstLink()) {
                $output->writeln('<error>The last mail has no link.</error>');

                return self::FAILURE;
            }

            $output->writeln($mail->getFirstLink());

            return self::SUCCESS;
        }

        $output->writeln([
            'Id: '.$mail->id,
            'Date: '.$mail->date->format('Y-m-d H:i:s'),
            'From: '.implode(', ', $mail->from),
            'To: '.implode(', ', $mail->recipients),
            'Subject: '.$mail->subject,
            '',
            (string) ($mail->text ?? strip_tags((string) $mail->html)),
        ]);

        if ([] !== $mail->links) {
            $output->writeln(['', 'Links:', ...$mail->links]);
        }

        return self::SUCCESS;
    }
}
