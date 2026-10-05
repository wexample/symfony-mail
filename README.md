# symfony-mail

Version: 2.0.2

## Usage

### Reading the mailbox

From the console:

```bash
bin/console mail:mailbox-last                      # the last mail
bin/console mail:mailbox-last ada@example.com      # the last mail delivered to this address, bcc included
bin/console mail:mailbox-last ada@example.com --link   # its first link only, to follow it from a script
bin/console mail:mailbox-purge                     # empties the mailbox
```

From a functional test, through `MailboxService`:

```php
$mail = static::getContainer()->get(MailboxService::class)->last('ada@example.com');

$client->request('GET', $mail->getFirstLink());
```

`MailboxMail` carries the subject, the addresses, the HTML and text bodies, the attachments (name, type, size) and every link of the bodies. `getRaw($id)` returns the mail as it would have left.

### Why a summary beside the .eml

The transport has the mail as an object when it keeps it: it writes what the page and the tests need then, so nothing has to parse MIME to read it back.

## Table of Contents

- [Usage](#usage)
- [Architecture](#architecture)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Architecture

- `Transport/MailboxTransportFactory` answers the `mailbox://` scheme. It is handed the event dispatcher: the Twig listener renders a `TemplatedEmail` on the `MessageEvent` the transport dispatches, and without it the mail is kept without a body.
- `Service/MailboxService` owns the directory format: it writes the mails (`store`) and reads them back (`list`, `last`, `find`, `getRaw`, `purge`). Ids start with the time the mail was kept, so sorting the ids sorts the mails; an id that does not match that format never reaches the filesystem.
- `Class/MailboxMail` is a mail read back from its summary.

Tests run in a fixture kernel (`tests/Fixtures/App`) whose mailer uses `mailbox://default`.

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

Visit the [Wexample Suite documentation](https://docs.wexample.com) for the complete package ecosystem.

## Dependencies

- php: >=8.5
- symfony/mailer: ^7.4
- symfony/mime: ^7.4 || ^8.0
- symfony/twig-bridge: ^7.4
- wexample/symfony-helpers: >=13.0.0
- wexample/symfony-loader: >=18.0.0
- wexample/symfony-translations: >=8.0.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
