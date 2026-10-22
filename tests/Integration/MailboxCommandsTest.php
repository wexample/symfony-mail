<?php

namespace Wexample\SymfonyMail\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MailboxCommandsTest extends AbstractMailboxTestCase
{
    public function testLastPrintsTheMailOfARecipient(): void
    {
        $this->sendWelcome('ada@app.test', 'Ada');
        $this->sendWelcome('bob@app.test', 'Bob');

        $tester = $this->runCommand('mail:mailbox-last', ['recipient' => 'ada@app.test']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Subject: Welcome Ada', $tester->getDisplay());
        $this->assertStringNotContainsString('Bob', $tester->getDisplay());
    }

    public function testLastPrintsOnlyTheLinkForAScript(): void
    {
        $this->sendWelcome('ada@app.test');

        $tester = $this->runCommand('mail:mailbox-last', ['--link' => true]);

        $this->assertSame("https://app.test/activate?token=abc&user=1\n", $tester->getDisplay());
    }

    public function testLastFailsOnAnEmptyMailbox(): void
    {
        $this->assertSame(Command::FAILURE, $this->runCommand('mail:mailbox-last', [])->getStatusCode());
    }

    public function testPurgeReportsWhatItRemoved(): void
    {
        $this->sendWelcome('ada@app.test');

        $tester = $this->runCommand('mail:mailbox-purge', []);

        $this->assertStringContainsString('1 mail(s) removed.', $tester->getDisplay());
        $this->assertSame([], $this->getMailbox()->list());
    }

    /**
     * @param array<string, mixed> $input
     */
    private function runCommand(
        string $name,
        array $input
    ): CommandTester {
        $tester = new CommandTester((new Application(self::$kernel))->find($name));
        $tester->execute($input);

        return $tester;
    }
}
