<?php

namespace Wexample\SymfonyMail\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Entity\Traits\HasDateCreatedTrait;
use Wexample\SymfonyMail\Enum\MailStatus;
use Wexample\SymfonyMail\Repository\MailRepository;

/**
 * One mail MailSenderService sent, or tried to: who it was for, what it was
 * about, how it ended. Recorded before it leaves, so a failed sending is kept.
 *
 * The body only for a mail sent with `keepBody` — one written by a visitor,
 * a contact message. A mail carrying a link or a code is kept without it:
 * the journal would otherwise hold what lets one into an account.
 *
 * Its getters follow MailboxMail, so the mailbox layout of symfony-mail-ds
 * draws one as well as the other.
 */
#[ORM\Entity(repositoryClass: MailRepository::class)]
#[ORM\Table(name: 'mail')]
#[ORM\Index(name: 'mail_date_created', columns: ['date_created'])]
class Mail extends AbstractEntity
{
    use HasDateCreatedTrait;

    /** @var list<string> addresses as written in the header, name included */
    #[ORM\Column(name: 'mail_from', type: Types::JSON)]
    private array $from;

    /** @var list<string> */
    #[ORM\Column(name: 'mail_to', type: Types::JSON)]
    private array $to;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $cc;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $bcc;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $replyTo;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $subject;

    /** The mail's own template, not the layout around it. */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $template;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true)]
    private ?string $locale;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $html = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $text = null;

    #[ORM\Column(type: Types::STRING, length: 16, enumType: MailStatus::class)]
    private MailStatus $status = MailStatus::PENDING;

    /**
     * @param list<string> $from
     * @param list<string> $to
     * @param list<string> $cc
     * @param list<string> $bcc
     * @param list<string> $replyTo
     */
    public function __construct(
        array $from,
        array $to,
        array $cc,
        array $bcc,
        array $replyTo,
        ?string $subject,
        ?string $template,
        ?string $locale,
    ) {
        parent::__construct();

        $this->from = $from;
        $this->to = $to;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->replyTo = $replyTo;
        $this->subject = $subject;
        $this->template = $template;
        $this->locale = $locale;
        $this->setDateCreatedNow();
    }

    public function keepBody(?string $html, ?string $text): void
    {
        $this->html = $html;
        $this->text = $text;
    }

    /**
     * The sender as it left: the mailer's own From (framework.mailer.headers)
     * is added on the way to a mail naming none.
     *
     * @param list<string> $from
     */
    public function setFrom(array $from): void
    {
        $this->from = $from;
    }

    public function setStatus(MailStatus $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): MailStatus
    {
        return $this->status;
    }

    public function getFrom(): array
    {
        return $this->from;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function getBcc(): array
    {
        return $this->bcc;
    }

    public function getReplyTo(): array
    {
        return $this->replyTo;
    }

    /**
     * Everyone it was addressed to, as MailboxMail names them.
     *
     * @return list<string>
     */
    public function getRecipients(): array
    {
        return [...$this->to, ...$this->cc, ...$this->bcc];
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function isBodyKept(): bool
    {
        return null !== $this->html || null !== $this->text;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->getDateCreated();
    }

    /** The journal keeps no attachment, as MailboxMail would list them. */
    public function getAttachments(): array
    {
        return [];
    }

    /** Nor the links: a kept body is a visitor's text, not a mail with actions. */
    public function getLinks(): array
    {
        return [];
    }
}
