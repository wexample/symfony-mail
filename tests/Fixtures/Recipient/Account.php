<?php

namespace Wexample\SymfonyMail\Tests\Fixtures\Recipient;

use Wexample\SymfonyMail\Interface\MailRecipientInterface;
use Wexample\SymfonyTranslations\Entity\Traits\HasLocaleTrait;
use Wexample\SymfonyTranslations\Interface\HasLocaleInterface;

/**
 * A recipient that speaks a language of its own.
 */
class Account implements MailRecipientInterface, HasLocaleInterface
{
    use HasLocaleTrait;

    public function __construct(
        private readonly string $email,
    ) {
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
