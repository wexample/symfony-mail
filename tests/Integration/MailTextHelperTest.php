<?php

namespace Wexample\SymfonyMail\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Wexample\SymfonyMail\Helper\MailTextHelper;

class MailTextHelperTest extends TestCase
{
    public function testALinkKeepsItsAddressAfterItsLabel(): void
    {
        $this->assertSame(
            'Activate: https://app.test/a?x=1&y=2',
            MailTextHelper::fromHtml('<a href="https://app.test/a?x=1&amp;y=2">Activate</a>')
        );
    }

    public function testALinkWhoseLabelIsItsAddressIsWrittenOnce(): void
    {
        $this->assertSame('https://app.test', MailTextHelper::fromHtml('<a href="https://app.test">https://app.test</a>'));
    }

    public function testBlocksBecomeParagraphsAndTheHeadIsDropped(): void
    {
        $this->assertSame(
            "Title\n\nFirst line\nsecond line\n\n- one\n- two",
            MailTextHelper::fromHtml(
                '<html><head><title>Ignored</title><style>p{}</style></head><body>'
                .'<h1>Title</h1>   <p>First   line<br>second line</p><ul><li>one</li><li>two</li></ul>'
                .'</body></html>'
            )
        );
    }
}
