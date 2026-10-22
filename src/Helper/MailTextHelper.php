<?php

namespace Wexample\SymfonyMail\Helper;

/**
 * The text part of a mail, from its HTML. A link keeps its address, written
 * after its label: in a text-only client, a sign-in mail whose link lost its
 * URL is a mail that cannot be used.
 */
final class MailTextHelper
{
    public static function fromHtml(string $html): string
    {
        $html = preg_replace('#<(head|style|script)\b.*?</\1>#is', '', $html);

        $html = preg_replace_callback(
            '#<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a>#is',
            function (array $match): string {
                $href = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5);
                $label = trim(html_entity_decode(strip_tags($match[3]), ENT_QUOTES | ENT_HTML5));

                if (! preg_match('#^(https?|mailto):#i', $href) || $label === $href) {
                    return '' !== $label ? $label : $href;
                }

                return '' !== $label ? $label.': '.$href : $href;
            },
            $html
        );

        $html = preg_replace('#<br\s*/?>#i', "\n", $html);
        $html = preg_replace('#<li\b[^>]*>#i', "\n- ", $html);
        $html = preg_replace('#</(p|div|tr|table|h[1-6]|ul|ol|blockquote)>#i', "\n\n", $html);

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
        $text = implode("\n", array_map(
            fn (string $line): string => trim(preg_replace('/[ \t]+/', ' ', $line)),
            explode("\n", $text)
        ));

        return trim(preg_replace("/\n{3,}/", "\n\n", $text));
    }
}
