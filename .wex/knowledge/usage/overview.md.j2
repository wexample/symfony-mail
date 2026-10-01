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
