<?php

namespace Wexample\SymfonyMail\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Wexample\SymfonyMail\Twig\MailExtension;

class MailboxUrlTest extends KernelTestCase
{
    public function testTheMailboxOpensOnTheRecipient(): void
    {
        self::bootKernel();
        $twig = static::getContainer()->get('twig');

        $this->assertSame(
            '/mailbox/?to=ada@app.test',
            $twig->createTemplate("{{ mailbox_url('ada@app.test') }}")->render()
        );
        $this->assertSame('/mailbox/', $twig->createTemplate('{{ mailbox_url() }}')->render());
    }

    public function testThereIsNoMailboxWhereItIsNotRouted(): void
    {
        $extension = new MailExtension(new UrlGenerator(new RouteCollection(), new RequestContext()));

        $this->assertNull($extension->mailboxUrl('ada@app.test'));
    }
}
