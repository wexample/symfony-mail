<?php

namespace Wexample\SymfonyMail\Twig;

use Twig\TwigFilter;
use Wexample\SymfonyHelpers\Twig\AbstractExtension;
use Wexample\SymfonyMail\Helper\MailTextHelper;

class MailExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'mail_text',
                [
                    MailTextHelper::class,
                    'fromHtml',
                ]
            ),
        ];
    }
}
