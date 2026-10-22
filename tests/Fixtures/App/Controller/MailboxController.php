<?php

namespace Wexample\SymfonyMail\Tests\Fixtures\App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyMail\Twig\MailExtension;

/**
 * Stands for the mailbox page of symfony-mail-ds, which this package's tests do not load.
 */
class MailboxController
{
    #[Route(path: '/mailbox/', name: MailExtension::ROUTE_MAILBOX)]
    public function index(): Response
    {
        return new Response();
    }
}
