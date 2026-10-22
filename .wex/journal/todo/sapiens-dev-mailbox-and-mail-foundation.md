# Sapiens — a development mailbox, and the mail foundation symfony-user will stand on

Opened: 2026-10-01
Updated: 2026-10-01
Author: agent:sapiens

## Context

Asked by the Sapiens app (`HOME_HABILIS/local/sapiens`). Sapiens wrote a development mailbox for itself, and none of it is specific to Sapiens: it belongs here. Today every mail of Sapiens comes from symfony-user (activation, password reset, sign-in code), sent by `SecurityMessageMailerSenderService`. That class already does, for symfony-user alone, things every mail of every application needs; they belong here too, so symfony-user can just use them.

Nothing below knows about patients, devices or establishments. Part 1 is what Sapiens extracts now; part 2 is what it will need shortly (a French / English site is planned); part 3 is optional.

## 1. Development mailbox (extracted from Sapiens)

What Sapiens has today, to be taken over and then deleted from it:
- `src/Mailer/DevMailboxTransport.php` and `src/Mailer/DevMailboxTransportFactory.php`;
- `src/Controller/Dev/MailboxController.php` and `templates/dev/mailbox.html.twig`;
- the factory registered under `when@dev` in `config/services.yaml`, with `MAILER_DSN=mailbox://default` in `.env.dev`.

### Transport
- A `mailbox://` DSN scheme: every mail is written as an `.eml` file into a directory instead of leaving the machine. The directory is configurable, `var/mailbox` by default.
- **Gotcha, already hit in Sapiens:** the factory must hand the event dispatcher to the transport. The Twig listener renders a `TemplatedEmail` on `MessageEvent`; without the dispatcher, the stored mail has no body.
- Registered in `dev` (and `test`, if part 3 is done) only. It must be impossible to enable in `prod` by mistake: refuse at boot, or do not register the factory there.
- File names that sort by time. A way to empty the mailbox: a button on the page and a console command.

### Page
- A page that lists the mails, newest first, with date, subject, sender and recipient.
- The HTML body is shown **isolated**, in a sandboxed `iframe`, not injected into the page: a mail's HTML must not inherit the application's styles or run in its origin. The text part, the attachments and the raw source can be opened too.
- A filter by recipient: in a scenario with several accounts, you look for the mail of one of them.
- The route exists in `dev` only. Sapiens has to open `^/dev/` to `PUBLIC_ACCESS` in its `access_control`, since the sign-in code is read there before being signed in. Document this, or pick a path that needs no rule. With `symfony-translations` locale routing, a path starting with `_` stays unprefixed.
- Rendered with the design system, like any other page of the stack.

## 2. Mail foundation, for symfony-user and the applications

What `SecurityMessageMailerSenderService` does for symfony-user alone, made available to every mail:
- **Texts next to the template.** A template reads its texts, subject included, from the `.trans.yml` beside it (`@mail::`). Today that domain switching is handled by symfony-user.
- **A mail layout.** A shared frame (header, footer, application name, legal footer) that an application overrides once, instead of every template repeating it. Sapiens will want its own look at skin time.
- **The sender** taken from `framework.mailer.headers.From`, as today.
- **The recipient's language.** Mails are often rendered by a Messenger worker, where there is no request, so the request locale means nothing there. A mail must be rendered in a locale given explicitly (the recipient's), with the default locale as fallback. Sapiens is going French / English; a language stored on the account will come from symfony-user, so this package only needs to accept a locale per mail.
- **Mails that carry a secret** (sign-in link, code) must not sit rendered in a queue. symfony-user solves this today by queueing a message without the secret and building the link in the worker, then handing the mail to the transport rather than to the mailer. Make that pattern available to any application ("build in the worker, send directly"), and document why.
- **No mail content in logs.** Bodies, links and codes are never logged, and failures are logged without the rendered mail. Any masking goes through the `symfony-security` maskers.

Once this exists, symfony-user's sender is rebuilt on top of it. That migration is symfony-user's task; agree on it with its agent.

## 3. Optional: reading the mailbox from tests and tools

Sapiens' acceptance tooling parses the `.eml` files itself to fetch the last mail sent to an account, follow its link or read its code. A small reader would make that a few lines in a functional test or an agent script:
- a service: the mails for a recipient, the last one, its links, its subject, its text;
- a console command that prints the last mail for a recipient.

Useful, but not required by Sapiens.

## Tests

- A templated mail sent through `mailbox://` is stored with its rendered body.
- The transport cannot be used in `prod`.
- The page lists the mails newest first, filters by recipient, and shows the HTML isolated.
- A mail rendered with an explicit locale uses that locale's texts, even when the current request uses another locale.
- A secret given to a mail never appears in a log record, including when sending fails.

## Then, in Sapiens

Require `wexample/symfony-mail`, then delete `src/Mailer/`, `src/Controller/Dev/MailboxController.php`, `templates/dev/mailbox.html.twig` and the `when@dev` block of `config/services.yaml`. `MAILER_DSN=mailbox://default` stays in `.env.dev`.
