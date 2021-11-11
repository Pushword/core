<?php

namespace Pushword\Core\Utils;

use Exception;

class F
{
    public static function file_get_contents(string $filename): string
    {
        $content = file_get_contents($filename);

        if (false === $content) {
            throw new Exception('Impossible to get content from `'.$filename.'`');
        }

        return $content;
    }

    /**
     * @return string|string[]
     */
    public static function preg_replace(string|array $pattern, string|array $replacement, string|array $subject, int $limit = -1, int &$count = 0): string|array // @phpstan-ignore-line
    {
        $return = preg_replace($pattern, $replacement, $subject, $limit, $count);

        if (\gettype($pattern) !== \gettype($return)) {
            throw new Exception('An error occured on preg_replace');
        }

        return $return; // @phpstan-ignore-line
    }
}
