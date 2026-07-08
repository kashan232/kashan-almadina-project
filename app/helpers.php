<?php

if (!function_exists('fmt_num')) {
    /**
     * Format a number with American grouping (1,234,567.89).
     */
    function fmt_num($number, int $decimals = 0, bool $trimTrailingZeros = false): string
    {
        if ($number === null || $number === '') {
            return $decimals > 0 ? number_format(0, $decimals, '.', ',') : '0';
        }

        if (!is_numeric($number)) {
            $number = str_replace(',', '', (string) $number);
        }

        $num = (float) $number;

        if (!is_finite($num)) {
            return (string) $number;
        }

        $formatted = number_format($num, $decimals, '.', ',');

        if ($trimTrailingZeros && $decimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $formatted;
    }
}

if (!function_exists('fmt_money')) {
    function fmt_money($number, int $decimals = 2): string
    {
        return fmt_num($number, $decimals);
    }
}

if (!function_exists('parse_num')) {
    function parse_num($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return (float) str_replace(',', '', (string) $value);
    }
}
