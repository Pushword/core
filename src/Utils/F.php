<?php

namespace Pushword\Core\Utils;

use Exception;

class F
{
    public static function file_get_contents(string $filename): string
    {
        $content = \Safe\file_get_contents($filename);

        return $content;
    }

    /**
     * @param string       $pattern
     * @param string|array $replacement
     * @param string|array $subject
     *
     * @return string
     */
    public static function preg_replace_str($pattern, $replacement, $subject, int $limit = -1, int &$count = 0) // @phpstan-ignore-line
    {
        $return = \Safe\preg_replace($pattern, $replacement, $subject, $limit, $count);

        //if (\gettype($pattern) !== \gettype($return)) {
        if (! \is_string($return)) {
            throw new Exception('An error occured on preg_replace');
        }

        return $return;
    }
}
