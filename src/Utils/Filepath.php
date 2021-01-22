<?php

namespace Pushword\Core\Utils;

class Filepath
{
    public static function removeExtension(string $filepath): string
    {
        $pos = strrpos($filepath, '.');

        return $pos ? substr($filepath, 0, $pos) : $filepath;
    }
}
