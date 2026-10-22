<?php

namespace Wexample\SymfonyMail\Service;

use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Wexample\SymfonyMail\Class\MailboxMail;

/**
 * The mailbox directory: each mail kept as `<id>.eml`, its raw source, and
 * `<id>.json`, the summary the page, the commands and the tests read. Ids
 * start with the time they were kept, so sorting them sorts the mails.
 */
class MailboxService
{
    private const string EXTENSION_RAW = 'eml';

    private const string EXTENSION_SUMMARY = 'json';

    /**
     * Without delimiters, so a route can take it as a requirement.
     */
    public const string ID_REQUIREMENT = '\d{8}-\d{6}-\d{6}-[0-9a-f]{6}';

    public function __construct(
        private readonly string $directory,
    ) {
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function store(SentMessage $sentMessage): MailboxMail
    {
        $message = $sentMessage->getOriginalMessage();
        $email = $message instanceof Email ? $message : null;
        $html = $email ? self::readBody($email->getHtmlBody()) : null;
        $text = $email ? self::readBody($email->getTextBody()) : null;
        $date = new DateTimeImmutable();

        $mail = new MailboxMail(
            id: $date->format('Ymd-His-u').'-'.bin2hex(random_bytes(3)),
            date: $date,
            subject: $email?->getSubject(),
            from: self::formatAddresses($email?->getFrom() ?? []),
            to: self::formatAddresses($email?->getTo() ?? []),
            cc: self::formatAddresses($email?->getCc() ?? []),
            recipients: array_map(
                fn (Address $address): string => strtolower($address->getAddress()),
                $sentMessage->getEnvelope()->getRecipients()
            ),
            html: $html,
            text: $text,
            attachments: array_map(
                fn (DataPart $part): array => [
                    'name' => $part->getFilename(),
                    'content_type' => $part->getContentType(),
                    'size' => strlen($part->getBody()),
                    'inline' => 'inline' === $part->getDisposition(),
                ],
                $email?->getAttachments() ?? []
            ),
            links: self::extractLinks($html, $text),
        );

        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }

        file_put_contents($this->buildPath($mail->id, self::EXTENSION_RAW), $sentMessage->toString());
        file_put_contents(
            $this->buildPath($mail->id, self::EXTENSION_SUMMARY),
            json_encode($mail->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );

        return $mail;
    }

    /**
     * @param string|null $recipient only the mails delivered to this address
     * @return list<MailboxMail> newest first
     */
    public function list(
        ?string $recipient = null,
        ?int $limit = null
    ): array {
        $mails = [];

        foreach ($this->listIds() as $id) {
            $mail = $this->find($id);

            if (null !== $mail && (null === $recipient || $mail->isFor($recipient))) {
                $mails[] = $mail;

                if (count($mails) === $limit) {
                    break;
                }
            }
        }

        return $mails;
    }

    public function last(?string $recipient = null): ?MailboxMail
    {
        return $this->list($recipient, 1)[0] ?? null;
    }

    public function isId(string $id): bool
    {
        return (bool) preg_match('/^'.self::ID_REQUIREMENT.'$/', $id);
    }

    /**
     * @return MailboxMail|null null for a mail that is not kept, or an id that is not one
     */
    public function find(string $id): ?MailboxMail
    {
        if (! $this->isId($id)) {
            return null;
        }

        $path = $this->buildPath($id, self::EXTENSION_SUMMARY);

        if (! is_file($path)) {
            return null;
        }

        return MailboxMail::fromArray(json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * The mail as it would have left: headers, every part, attachments.
     */
    public function getRaw(string $id): ?string
    {
        if (! $this->isId($id)) {
            return null;
        }

        $path = $this->buildPath($id, self::EXTENSION_RAW);

        return is_file($path) ? (string) file_get_contents($path) : null;
    }

    /**
     * Every recipient of the kept mails, for a filter.
     *
     * @return list<string>
     */
    public function listRecipients(): array
    {
        $recipients = [];
        foreach ($this->list() as $mail) {
            array_push($recipients, ...$mail->recipients);
        }

        $recipients = array_values(array_unique($recipients));
        sort($recipients);

        return $recipients;
    }

    /**
     * @return int the number of mails removed
     */
    public function purge(): int
    {
        $ids = $this->listIds();

        foreach ($ids as $id) {
            foreach ([self::EXTENSION_RAW, self::EXTENSION_SUMMARY] as $extension) {
                $path = $this->buildPath($id, $extension);

                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        return count($ids);
    }

    /**
     * @return list<string> newest first
     */
    private function listIds(): array
    {
        $ids = array_map(
            fn (string $path): string => basename($path, '.'.self::EXTENSION_SUMMARY),
            glob($this->directory.'/*.'.self::EXTENSION_SUMMARY) ?: []
        );
        rsort($ids);

        return $ids;
    }

    /**
     * An id comes from a URL or a command line: one that is not ours never
     * reaches the filesystem.
     */
    private function buildPath(
        string $id,
        string $extension
    ): string {
        if (! $this->isId($id)) {
            throw new InvalidArgumentException('Not a mailbox id: '.$id);
        }

        return $this->directory.'/'.$id.'.'.$extension;
    }

    /**
     * @param resource|string|null $body
     */
    private static function readBody(mixed $body): ?string
    {
        if (is_resource($body)) {
            rewind($body);

            return (string) stream_get_contents($body);
        }

        return $body;
    }

    /**
     * @param Address[] $addresses
     * @return list<string>
     */
    private static function formatAddresses(array $addresses): array
    {
        return array_values(array_map(fn (Address $address): string => $address->toString(), $addresses));
    }

    /**
     * @return list<string>
     */
    private static function extractLinks(
        ?string $html,
        ?string $text
    ): array {
        $links = [];

        if (null !== $html && preg_match_all('/href\s*=\s*(["\'])(.*?)\1/i', $html, $matches)) {
            foreach ($matches[2] as $href) {
                $links[] = html_entity_decode($href, ENT_QUOTES | ENT_HTML5);
            }
        }

        // A text part drawn from HTML by a converter that only strips the
        // tags still carries its entities: `&amp;` between two parameters.
        if (null !== $text && preg_match_all('#https?://[^\s<>"\']+#i', $text, $matches)) {
            foreach ($matches[0] as $link) {
                $links[] = html_entity_decode($link, ENT_QUOTES | ENT_HTML5);
            }
        }

        return array_values(array_unique(array_filter(
            $links,
            fn (string $link): bool => (bool) preg_match('#^https?://#i', $link)
        )));
    }
}
