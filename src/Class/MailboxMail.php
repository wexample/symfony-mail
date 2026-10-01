<?php

namespace Wexample\SymfonyMail\Class;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * A mail the mailbox kept, as it was sent. Read back from the summary written
 * beside the .eml, so nothing parses MIME to show it.
 */
final readonly class MailboxMail
{
    /**
     * @param list<string> $from addresses as written in the header, name included
     * @param list<string> $to
     * @param list<string> $cc
     * @param list<string> $recipients bare addresses the mail was delivered to, bcc included
     * @param list<array{name: string|null, content_type: string, size: int, inline: bool}> $attachments
     * @param list<string> $links every URL of the HTML and text bodies, in order of appearance
     */
    public function __construct(
        public string $id,
        public DateTimeImmutable $date,
        public ?string $subject,
        public array $from,
        public array $to,
        public array $cc,
        public array $recipients,
        public ?string $html,
        public ?string $text,
        public array $attachments,
        public array $links,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            new DateTimeImmutable($data['date']),
            $data['subject'],
            $data['from'],
            $data['to'],
            $data['cc'],
            $data['recipients'],
            $data['html'],
            $data['text'],
            $data['attachments'],
            $data['links'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format(DateTimeInterface::RFC3339_EXTENDED),
            'subject' => $this->subject,
            'from' => $this->from,
            'to' => $this->to,
            'cc' => $this->cc,
            'recipients' => $this->recipients,
            'html' => $this->html,
            'text' => $this->text,
            'attachments' => $this->attachments,
            'links' => $this->links,
        ];
    }

    public function isFor(string $address): bool
    {
        return in_array(strtolower(trim($address)), $this->recipients, true);
    }

    public function getFirstLink(): ?string
    {
        return $this->links[0] ?? null;
    }
}
