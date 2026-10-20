<?php

namespace Wexample\SymfonyMail\Repository;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;
use Wexample\SymfonyMail\Entity\Mail;
use Wexample\SymfonyMail\Enum\MailStatus;

/**
 * @method Mail saveNewMail(TemplatedEmail $email, ?string $template)
 */
class MailRepository extends AbstractRepository
{
    public static function getEntityClassName(): string
    {
        return Mail::class;
    }

    /**
     * @param string|null $template the mail's own template, before the layout takes its place
     */
    public function createNewMail(TemplatedEmail $email, ?string $template): Mail
    {
        return new Mail(
            self::addresses($email->getFrom()),
            self::addresses($email->getTo()),
            self::addresses($email->getCc()),
            self::addresses($email->getBcc()),
            self::addresses($email->getReplyTo()),
            $email->getSubject(),
            $template,
            $email->getLocale(),
        );
    }

    /**
     * How it ended, and its sender as the transport sent it: the transport
     * works on a copy, to which the mailer's own From is added.
     */
    public function saveStatus(Mail $mail, MailStatus $status, ?SentMessage $sent = null): void
    {
        $mail->setStatus($status);

        if ($sent?->getOriginalMessage() instanceof Email) {
            $mail->setFrom(self::addresses($sent->getOriginalMessage()->getFrom()));
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @param list<Address> $list
     * @return list<string>
     */
    private static function addresses(array $list): array
    {
        return array_map(static fn (Address $address): string => $address->toString(), $list);
    }

    /**
     * The latest first.
     *
     * @return list<Mail>
     */
    public function findLatest(int $limit): array
    {
        return $this->findBy([], ['dateCreated' => self::SORT_DESC], $limit);
    }
}
