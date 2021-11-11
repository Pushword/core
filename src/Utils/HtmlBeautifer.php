<?php

namespace Pushword\Core\Utils;

class HtmlBeautifer
{
    public static function removeHtmlComments(string $content): string
    {
        return (string) \Safe\preg_replace('/<!--(.|\s)*?-->/', '', $content);
    }

    public static function punctuationBeautifer(string $text): string
    {
        $text = (string) \Safe\preg_replace('# ([\!\?\:;])([^a-zA-Z]|$)#', '&nbsp;$1$2', $text);
        // avoid to catch tailwind selector inside ""

        return str_replace(
            ['« ', ' »', '&laquo; ', ' &raquo;'],
            ['«&nbsp;', '&nbsp;»', '&laquo;&nbsp;', '&nbsp;&raquo;'],
            $text
        );
    }
}
