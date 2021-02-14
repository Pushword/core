<?php

namespace Pushword\Core\Utils;

class FilesizeFormatter
{
    static function formatBytes($size, $precision = 2)
    {
        $base = log((float) $size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }
}
