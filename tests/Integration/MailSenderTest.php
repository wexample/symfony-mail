<?php

namespace Wexample\SymfonyMail\Tests\Integration;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportException;
use Wexample\SymfonyMail\Service\MailSenderService;
use Wexample\SymfonyMail\Tests\Fixtures\Log\RecordingLogger;
use Wexample\SymfonyMail\Tests\Fixtures\Transport\RefusingTransport;
use Wexample\SymfonyTranslations\Translation\Translator;

class MailSenderTest extends AbstractMailboxTestCase
{
    private const string SECRET_LINK = 'https://app.test/login?token=s3cr3t-t0k3n';

    public function testTheTextsComeFromTheYmlBesideTheTemplate(): void
    {
        $this->getSender()->send($this->buildGreeting());

        $mail = $this->getMailbox()->last('ada@app.test');

        $this->assertSame('Hello', $mail->subject);
        $this->assertStringContainsString('Hello Ada, here is your link.', (string) $mail->html);
        $this->assertSame([self::SECRET_LINK], $mail->links);
    }

    public function testTheHtmlIsDrawnInTheLayout(): void
    {
        $this->getSender()->send($this->buildGreeting());

        $html = (string) $this->getMailbox()->last()->html;

        $this->assertStringContainsString('<html lang="en">', $html);
        $this->assertStringContainsString('Fixture App', $html);
        $this->assertStringContainsString('This mail was sent automatically', $html);
    }

    public function testTheTextPartKeepsTheLinksAddresses(): void
    {
        $this->getSender()->send($this->buildGreeting());

        $this->assertSame(
            "Fixture App\n\nHello Ada, here is your link.\n\nSign in: ".self::SECRET_LINK."\n\n--\nThis mail was sent automatically, please do not reply to it.\n",
            $this->getMailbox()->last()->text
        );
    }

    public function testTheSenderIsTheMailers(): void
    {
        $this->getSender()->send($this->buildGreeting());

        $this->assertSame(['"App" <no-reply@app.test>'], $this->getMailbox()->last()->from);
    }

    public function testTheLocaleGivenWinsOverTheCurrentOne(): void
    {
        $translator = $this->getTranslator();
        $translator->setLocale('en');

        $this->getSender()->send($this->buildGreeting(), 'fr');

        $mail = $this->getMailbox()->last();
        $this->assertSame('Bonjour', $mail->subject);
        $this->assertStringContainsString('Bonjour Ada, voici votre lien.', (string) $mail->html);
        $this->assertStringContainsString('<html lang="fr">', (string) $mail->html);
        $this->assertStringContainsString('merci de ne pas y répondre', (string) $mail->html);

        // The request goes on in its own language.
        $this->assertSame('en', $translator->getLocale());
    }

    public function testWithoutALocaleTheDefaultOneIsUsedNotTheCurrentOne(): void
    {
        $this->getTranslator()->setLocale('fr');

        $this->getSender()->send($this->buildGreeting());

        $this->assertSame('Hello', $this->getMailbox()->last()->subject);
    }

    public function testATextOnlyMailIsSentWithoutTheLayout(): void
    {
        $this->getSender()->send(
            (new TemplatedEmail())
                ->to('ada@app.test')
                ->textTemplate('@front/mails/notice.txt.twig'),
            'fr'
        );

        $mail = $this->getMailbox()->last();
        $this->assertSame('Avis', $mail->subject);
        $this->assertNull($mail->html);
        $this->assertSame("Un avis en texte seul.\n", $mail->text);
    }

    public function testASecretNeverReachesTheLogs(): void
    {
        $logger = static::getContainer()->get('logger');
        $this->getSender()->send($this->buildGreeting());

        $refusing = new MailSenderService(
            new RefusingTransport(static::getContainer()->get('event_dispatcher'), $logger),
            $this->getTranslator(),
            '@WexampleSymfonyMailBundle/mails/layout.html.twig',
            '@WexampleSymfonyMailBundle/mails/layout.txt.twig',
            null,
            'en',
        );

        try {
            $refusing->send($this->buildGreeting());
            $this->fail('The refusing transport sent the mail.');
        } catch (TransportException) {
        }

        $this->assertInstanceOf(RecordingLogger::class, $logger);
        foreach ($logger->records as $record) {
            $this->assertStringNotContainsString('s3cr3t-t0k3n', $record);
        }
    }

    private function buildGreeting(): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->to('ada@app.test')
            ->htmlTemplate('@front/mails/greeting.html.twig')
            ->context([
                'name' => 'Ada',
                'link' => self::SECRET_LINK,
            ]);
    }

    private function getSender(): MailSenderService
    {
        return static::getContainer()->get('test.sender');
    }

    private function getTranslator(): Translator
    {
        return static::getContainer()->get('test.translator');
    }
}
