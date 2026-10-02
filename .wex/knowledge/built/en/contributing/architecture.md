## Architecture

- `Transport/MailboxTransportFactory` answers the `mailbox://` scheme. It is handed the event dispatcher: the Twig listener renders a `TemplatedEmail` on the `MessageEvent` the transport dispatches, and without it the mail is kept without a body.
- `Service/MailboxService` owns the directory format: it writes the mails (`store`) and reads them back (`list`, `last`, `find`, `getRaw`, `purge`). Ids start with the time the mail was kept, so sorting the ids sorts the mails; an id that does not match that format never reaches the filesystem.
- `Class/MailboxMail` is a mail read back from its summary.

Tests run in a fixture kernel (`tests/Fixtures/App`) whose mailer uses `mailbox://default`.
