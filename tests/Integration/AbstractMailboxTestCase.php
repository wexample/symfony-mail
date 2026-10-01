<?php

namespace Wexample\SymfonyMail\Tests\Integration;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Wexample\SymfonyMail\Service\MailboxService;

abstract class AbstractMailboxTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $this->getMailbox()->purge();
    }

    protected function getMailbox(): MailboxService
    {
        return static::getContainer()->get(MailboxService::class);
    }

    protected function sendWelcome(
        string $to,
        string $name = 'Ada'
    ): void {
        static::getContainer()->get('test.mailer')->send(
            (new TemplatedEmail())
                ->from('App <noreply@app.test>')
                ->to($to)
                ->subject('Welcome '.$name)
                ->htmlTemplate('mails/welcome.html.twig')
                ->context(['name' => $name])
        );
    }
}
