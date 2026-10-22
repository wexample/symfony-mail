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
