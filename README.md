# symfony-mail

Version: 3.0.5

Mails for Symfony: a sender every mail goes through, and a development mailbox

## Table of Contents

- [Installation](#installation)
- [Introduction](#introduction)

## Installation

```php
// config/bundles.php
Wexample\SymfonyMail\WexampleSymfonyMailBundle::class => ['all' => true],
```

### Development mailbox

```dotenv
# .env.dev
MAILER_DSN=mailbox://default
```

Every mail is then kept in `var/mailbox` instead of being sent: its raw source as `<id>.eml`, and a summary as `<id>.json`. The directory is set by `wexample_symfony_mail.mailbox.directory`.

The transport is refused in `prod`: a mail kept on disk is a mail its recipient never gets. The refusal is raised when the mailer first creates its transport, at the first send.

## Introduction

Mails for Symfony applications:
- `MailSenderService`, the sender every mail of an application goes through: texts beside the template, a layout, a text part that keeps its links, the recipient's locale, and no rendered mail in a queue or a log (see usage/sending);
- a development mailbox, where every mail lands instead of leaving the machine, readable by a page (symfony-mail-ds), a console command and the application's tests.
