<?php

namespace Wexample\SymfonyMail\Enum;

/**
 * Where a mail of the journal stands once handed to the transport.
 */
enum MailStatus: string
{
    /** Recorded, the transport not answered yet — a mail left so has its request dead mid-way. */
    case PENDING = 'pending';

    case SENT = 'sent';

    /** Refused by the transport: its error went up to the caller. */
    case FAILED = 'failed';

    /** Not sent: every recipient was on a domain nobody receives at. */
    case DROPPED = 'dropped';
}
