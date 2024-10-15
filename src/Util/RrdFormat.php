<?php

namespace IMEdge\Web\Grapher\Util;

use RuntimeException;

use function implode;
use function localeconv;
use function number_format;

class RrdFormat
{
    public static function seconds(int $value): string
    {
        $steps = [
            86400 => 'd',
            3600  => 'h',
            60    => 'm',
        ];

        $remain = $value;
        $parts = [];
        foreach ($steps as $div => $unit) {
            if ($remain > $div) {
                $current = \floor($remain / $div);
                $remain = $remain % $div;
                $parts[] = "$current$unit";
            }
        }
        if ($remain > 0) {
            $parts[] = $remain . 's';
        }

        return implode(' ', $parts);
    }

    /**
     * @param int|float|string $number
     */
    public static function number($number): string
    {
        if (in_array($number, ['NAN', 'INF', '-INF'])) {
            return $number;
        }
        if (is_string($number)) {
            throw new RuntimeException('Unexpected string in ::number(): ' . $number);
        }

        $locale = localeconv();
        return number_format(
            $number,
            $locale['frac_digits'],
            $locale['decimal_point'],
            $locale['thousands_sep']
        );
    }

    public static function percent(float $value): string
    {
        return sprintf('%.2g%%', $value * 100);
    }
}
