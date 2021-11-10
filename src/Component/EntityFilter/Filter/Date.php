<?php

namespace Pushword\Core\Component\EntityFilter\Filter;

use Pushword\Core\AutowiringTrait\RequiredAppTrait;

class Date extends AbstractFilter
{
    use RequiredAppTrait;

    /**
     * @return string
     */
    public function apply($propertyValue)
    {
        return $this->convertDateShortCode(\strval($propertyValue), $this->getApp()->getDefaultLocale());
    }

    private function convertDateShortCode(string $string, ?string $locale = null): string
    {
        //var_dump($string); exit;
        if (null !== $locale) {
            setlocale(\LC_TIME, $this->convertLocale($locale));
        }

        //$string = preg_replace('/date\([\'"]?([a-z% ]+)[\'"]?\)/i',
        //  strftime(strpos('\1', '%') ? '\1': '%\1'), $string);
        $string = \strval(preg_replace('/date\([\'"]?%?Y[\'"]?\)/i', (string) strftime('%Y'), $string));
        $string = \strval(preg_replace('/date\([\'"]?%?(B|M)[\'"]?\)/i', (string) strftime('%B'), $string));
        $string = \strval(preg_replace('/date\([\'"]?%?A[\'"]?\)/i', (string) strftime('%A'), $string));
        $string = \strval(preg_replace('/date\([\'"]?%?e[\'"]?\)/i', (string) strftime('%e'), $string));

        return $string;
    }

    private function convertLocale(string $locale): string
    {
        if ('fr' == $locale) {
            return 'fr_FR';
        }

        if ('en' == $locale) {
            return 'en_UK';
        }

        return $locale;
    }
}
