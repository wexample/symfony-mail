<?php

namespace Wexample\SymfonyMail\Service;

use LogicException;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Wexample\SymfonyMail\Enum\MailStatus;
use Wexample\SymfonyMail\Repository\MailRepository;
use Wexample\SymfonyMail\Interface\MailRecipientInterface;
use Wexample\SymfonyTranslations\Interface\HasLocaleInterface;
use Twig\Environment;
use Wexample\SymfonyTranslations\Translation\Translator;

/**
 * Where a mail of the application leaves from.
 *
 * - Its texts, subject included, come from the yml beside its template, read
 *   as `@mail::`.
 * - Its HTML is drawn inside the layout (wexample_symfony_mail.layout), and
 *   its text part is the same template with its links written out
 *   (text_layout); a text-only mail is sent as it is.
 * - It is rendered in the locale given, the recipient's, or else the default
 *   locale — never the request's: the request may be another user's, or none
 *   when a worker sends.
 * - Its sender is the mailer's (`framework.mailer.headers.From`) unless the
 *   mail names one.
 *
 * Handed to the transport, not to the mailer: the mailer queues on Messenger
 * when the application routes SendEmailMessage, and the queue would keep the
 * rendered mail, with whatever link or code it carries. To send in the
 * background, queue a message naming what to send — never the secret — and
 * call this from its handler, where the secret is built.
 *
 * Each mail is recorded in the journal (Mail) before it leaves, and its end
 * after: sent, failed, or dropped for want of a recipient who receives. Its
 * body only when the caller says it may be kept (`keepBody`): never a mail
 * carrying a link or a code.
 *
 * Nothing here logs; a failure bubbles up as the transport raised it.
 */
class MailSenderService
{
    public const string CONTEXT_TEMPLATE = 'mail_template';

    public const string CONTEXT_LOCALE = 'mail_locale';

    public const string CONTEXT_APP_NAME = 'mail_app_name';

    public function __construct(
        private readonly TransportInterface $transport,
        private readonly Translator $translator,
        private readonly string $layout,
        private readonly string $textLayout,
        private readonly ?string $appName,
        private readonly string $defaultLocale,
        private readonly MailRepository $journal,
        private readonly Environment $twig,
    ) {
    }

    /**
     * @param string|null $locale the recipient's language; the default locale when unknown
     * @param bool $keepBody whether the journal keeps what the mail says: only for one carrying no secret
     * @throws LogicException for a mail without a template, whose texts would have nowhere to come from
     */
    public function send(
        TemplatedEmail $email,
        ?string $locale = null,
        bool $keepBody = false
    ): ?SentMessage {
        $template = $email->getHtmlTemplate()
            ?? $email->getTextTemplate()
            ?? throw new LogicException('A mail sent by MailSenderService has an HTML or a text template.');
        $locale ??= $this->defaultLocale;

        $email->locale($locale);

        if (null !== $email->getHtmlTemplate()) {
            $email
                ->htmlTemplate($this->layout)
                ->textTemplate($email->getTextTemplate() ?? $this->textLayout)
                ->context($email->getContext() + [
                    self::CONTEXT_TEMPLATE => $template,
                    self::CONTEXT_LOCALE => $locale,
                    self::CONTEXT_APP_NAME => $this->appName,
                ]);
        }

        // Held for the rendering too, which the transport does within send().
        $this->translator->setDomainFromTemplatePath(Translator::DOMAIN_TYPE_MAIL, $template);

        try {
            if (null === $email->getSubject()) {
                $email->subject($this->translator->trans('@mail::subject', locale: $locale));
            }

            $mail = $this->journal->createNewMail($email, $template);

            if ($keepBody) {
                // Drawn now rather than by the transport, to be kept; the
                // transport finds it drawn and leaves it. Twig's own renderer,
                // which exists only where the mailer is configured.
                (new BodyRenderer($this->twig))->render($email);
                $mail->keepBody($email->getHtmlBody(), $email->getTextBody());
            }

            $this->journal->save($mail, flush: true);

            try {
                $sent = $this->transport->send($email);
            } catch (TransportExceptionInterface $exception) {
                $this->journal->saveStatus($mail, MailStatus::FAILED);

                throw $exception;
            }

            $this->journal->saveStatus($mail, null === $sent ? MailStatus::DROPPED : MailStatus::SENT, $sent);

            return $sent;
        } finally {
            $this->translator->revertDomain(Translator::DOMAIN_TYPE_MAIL);
        }
    }

    /**
     * Sends the mail to this recipient, in its language when it has one.
     *
     * @throws LogicException for a recipient without an address
     */
    public function sendTo(
        MailRecipientInterface $recipient,
        TemplatedEmail $email
    ): ?SentMessage {
        $address = $recipient->getEmail()
            ?? throw new LogicException('A mail recipient has an address.');

        return $this->send(
            $email->to(new Address($address)),
            $recipient instanceof HasLocaleInterface ? $recipient->getLocale() : null
        );
    }
}
