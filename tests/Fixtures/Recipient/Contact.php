<?php

namespace Wexample\SymfonyMail\Tests\Fixtures\Recipient;

use Wexample\SymfonyMail\Interface\MailRecipientInterface;

/**
 * A recipient without a language of its own.
 */
class Contact implements MailRecipientInterface
{
    public function __construct(
        private readonly ?string $email,
    ) {
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
