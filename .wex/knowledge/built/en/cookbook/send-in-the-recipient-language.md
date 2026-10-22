## Send a mail in the recipient's language

A mail is often sent outside the recipient's own request: a Stripe webhook, a Messenger worker, an administrator's action. The request at hand says nothing about the recipient's language, so the language has to be stored on the recipient beforehand.

### The recipient

An entity a mail is sent to implements `MailRecipientInterface` (`getEmail()`). If it also implements `HasLocaleInterface` from symfony-translations, usually through `HasLocaleTrait` (a nullable `locale` column), it receives its mails in that language:

```php
class Customer implements MailRecipientInterface, HasLocaleInterface
{
    use HasEmailTrait;
    use HasLocaleTrait;
}

$mailSender->sendTo($customer, (new TemplatedEmail())->htmlTemplate('@front/mails/payment_received.html.twig'));
```

A recipient without a language, or with none stored yet, receives the default locale.

symfony-user's `AbstractUser` already implements `HasLocaleInterface`. Adding it gave every application's user table a nullable `locale` column, so each application generates a migration once.

### Filling the language

It is up to the application, which knows when the language is chosen:

- **With a language switcher and URL prefixes** (symfony-translations `locale_routing`), set `wexample_symfony_user.remember_locale: true`. An account reading a page whose URL names a language keeps that language. A language the browser merely asks for is not stored, and neither is the default one.
- **Without a switcher**, set it where the language is known. At sign-up: `$user->setLocale($request->getLocale())`. At checkout: store it on the order, or pass it in the payment provider's metadata, and read it back in the webhook:

  ```php
  // Checkout, while the customer's request is at hand.
  $session = $stripe->checkout->sessions->create([..., 'metadata' => ['locale' => $request->getLocale()]]);

  // Webhook, in Stripe's request.
  $mailSender->send($email->to($customerEmail), $session->metadata['locale'] ?? null);
  ```
- **Nothing at all**: every mail goes out in the default locale.
