<?php

namespace Wexample\SymfonyMail\Service;

use LogicException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
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
    ) {
    }

    /**
     * @param string|null $locale the recipient's language; the default locale when unknown
     * @throws LogicException for a mail without a template, whose texts would have nowhere to come from
     */
    public function send(
        TemplatedEmail $email,
        ?string $locale = null
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

            return $this->transport->send($email);
        } finally {
            $this->translator->revertDomain(Translator::DOMAIN_TYPE_MAIL);
        }
    }
}
