## Sending a mail

Every mail of an application goes through `MailSenderService::send()`:

```php
$sender->send(
    (new TemplatedEmail())
        ->to($user->getEmail())
        ->htmlTemplate('@front/mails/welcome.html.twig')
        ->context(['link' => $link]),
    $user->getLocale(), // null: the default locale
);
```

- **Texts beside the template.** `welcome.html.twig` reads its texts from `welcome.trans.yml`, beside it, as `@mail::key`. The subject is `@mail::subject`, unless the mail sets one.
- **Layout.** The HTML is drawn inside `wexample_symfony_mail.layout`, which carries the application name (`app_name`) and a footer. An application gives its own layout, which may `{% extends '@WexampleSymfonyMailBundle/mails/layout.html.twig' %}` and override the `header`, `body` or `footer` block. The template it frames is in `mail_template`, the locale in `mail_locale`.
- **Text part.** Built from the same template, without the frame, each link written as `label: URL` (`text_layout`). A mail with only a `textTemplate` is sent as it is, without the frame.
- **Locale.** The mail is rendered in the locale given, and in the default locale when none is given, never in the request's: the request may be another user's, or there is none when a worker sends.
- **A recipient entity.** `$sender->sendTo($recipient, $email)` addresses the mail to a `MailRecipientInterface` and renders it in its language when the recipient is also `HasLocaleInterface`. See the cookbook *Send a mail in the recipient's language*.
- **Sender.** `framework.mailer.headers.From`, unless the mail names one.

### A mail carrying a secret

A sign-in link, a reset link, a code: the rendered mail must never sit in a queue, a retry or a failed transport. `send()` hands the mail to the transport and not to the mailer, which would queue it on Messenger where the application routes `SendEmailMessage`.

To send in the background anyway, queue a message that names what to send and never the secret (an account id, a kind of mail), and build the secret in its handler, just before calling `send()`. symfony-user's `SendSecurityMessage` does this.

### Logs

The sender logs nothing. A failure is raised as the transport raised it, without the rendered mail. A caller that records failures records the kind of mail and the account, never the body, the link or the code.
