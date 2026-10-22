<?php

namespace Wexample\SymfonyMail\Tests\Integration;

use LogicException;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Email;
use Wexample\SymfonyMail\Transport\MailboxTransportFactory;

class MailboxTransportTest extends AbstractMailboxTestCase
{
    public function testATemplatedMailIsKeptWithItsRenderedBody(): void
    {
        $this->sendWelcome('ada@app.test');

        $mail = $this->getMailbox()->last();

        $this->assertNotNull($mail);
        $this->assertSame('Welcome Ada', $mail->subject);
        $this->assertSame(['"App" <noreply@app.test>'], $mail->from);
        $this->assertSame(['ada@app.test'], $mail->recipients);
        $this->assertStringContainsString('<p>Welcome Ada.</p>', (string) $mail->html);
        $this->assertSame(['https://app.test/activate?token=abc&user=1'], $mail->links);
        $this->assertStringContainsString('Subject: Welcome Ada', (string) $this->getMailbox()->getRaw($mail->id));
    }

    public function testMailsAreListedNewestFirstAndFilteredByRecipient(): void
    {
        $this->sendWelcome('ada@app.test', 'Ada');
        $this->sendWelcome('bob@app.test', 'Bob');
        $this->sendWelcome('Ada@App.test', 'Ada again');

        $this->assertSame(
            ['Welcome Ada again', 'Welcome Bob', 'Welcome Ada'],
            array_map(fn ($mail) => $mail->subject, $this->getMailbox()->list())
        );
        $this->assertSame(
            ['Welcome Ada again', 'Welcome Ada'],
            array_map(fn ($mail) => $mail->subject, $this->getMailbox()->list('ADA@app.test'))
        );
        $this->assertSame('Welcome Bob', $this->getMailbox()->last('bob@app.test')?->subject);
        $this->assertNull($this->getMailbox()->last('nobody@app.test'));
        $this->assertSame(['ada@app.test', 'bob@app.test'], $this->getMailbox()->listRecipients());
    }

    public function testBlindCopiesAndAttachmentsAreKept(): void
    {
        static::getContainer()->get('test.mailer')->send(
            (new Email())
                ->from('noreply@app.test')
                ->to('ada@app.test')
                ->bcc('audit@app.test')
                ->subject('Invoice')
                ->text('See https://app.test/invoice/1 attached.')
                ->attach('%PDF-1.4 fake', 'invoice.pdf', 'application/pdf')
        );

        $mail = $this->getMailbox()->last('audit@app.test');

        $this->assertNotNull($mail);
        $this->assertSame(['ada@app.test'], $mail->to);
        $this->assertNull($mail->html);
        $this->assertSame(['https://app.test/invoice/1'], $mail->links);
        $this->assertSame(
            [['name' => 'invoice.pdf', 'content_type' => 'application/pdf', 'size' => 13, 'inline' => false]],
            $mail->attachments
        );
    }

    public function testALinkWrittenWithItsEntitiesInTheTextIsTheSameLink(): void
    {
        static::getContainer()->get('test.mailer')->send(
            (new Email())
                ->from('noreply@app.test')
                ->to('ada@app.test')
                ->subject('Sign in')
                ->html('<a href="https://app.test/login?user=ada&amp;hash=x">https://app.test/login?user=ada&amp;hash=x</a>')
                ->text('https://app.test/login?user=ada&amp;hash=x')
        );

        $this->assertSame(['https://app.test/login?user=ada&hash=x'], $this->getMailbox()->last()->links);
    }

    public function testPurgeEmptiesTheMailbox(): void
    {
        $this->sendWelcome('ada@app.test');
        $this->sendWelcome('bob@app.test');

        $this->assertSame(2, $this->getMailbox()->purge());
        $this->assertSame([], $this->getMailbox()->list());
    }

    public function testAnIdThatIsNotOursNeverReachesTheFilesystem(): void
    {
        $this->assertNull($this->getMailbox()->getRaw('../../config/config'));
        $this->assertNull($this->getMailbox()->find('../../config/config'));
    }

    public function testTheTransportIsRefusedInProd(): void
    {
        $factory = new MailboxTransportFactory($this->getMailbox(), 'prod');

        $this->assertTrue($factory->supports(Dsn::fromString('mailbox://default')));

        $this->expectException(LogicException::class);
        $factory->create(Dsn::fromString('mailbox://default'));
    }
}
