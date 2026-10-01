<?php

namespace Wexample\SymfonyMail\Interface;

/**
 * Something a mail can be sent to: an account, a contact. One that also
 * implements HasLocaleInterface (symfony-translations) receives its mails in
 * its own language.
 */
interface MailRecipientInterface
{
    public function getEmail(): ?string;
}
