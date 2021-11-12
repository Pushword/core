<?php

namespace Pushword\Core\Component\EntityFilter\Filter;

use Pushword\Core\AutowiringTrait\RequiredAppTrait;
use Pushword\Core\Utils\F;

class Date extends AbstractFilter
{
    use RequiredAppTrait;

    public function apply($propertyValue): string
    {
        return $this->convertDateShortCode(\strval($propertyValue), $this->getApp()->getDefaultLocale());
    }

    /** @psalm-suppress RedundantCast */
    private function convertDateShortCode(string $string, ?string $locale = null): string
    {
        //var_dump($string); exit;
        if (null !== $locale) {
            setlocale(\LC_TIME, $this->convertLocale($locale));
        }

        //$string = preg_replace('/date\([\'"]?([a-z% ]+)[\'"]?\)/i',
        //  strftime(strpos('\1', '%') ? '\1': '%\1'), $string);
        $string = F::preg_replace_str('/date\([\'"]?%?Y[\'"]?\)/i', (string) strftime('%Y'), $string);
        $string = F::preg_replace_str('/date\([\'"]?%?(B|M)[\'"]?\)/i', (string) strftime('%B'), $string);
        $string = F::preg_replace_str('/date\([\'"]?%?A[\'"]?\)/i', (string) strftime('%A'), $string);

        return F::preg_replace_str('/date\([\'"]?%?e[\'"]?\)/i', (string) strftime('%e'), $string);
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
