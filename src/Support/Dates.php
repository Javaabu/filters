<?php

namespace Javaabu\Filters\Support;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Strict parsing of dates from the query string, and formatting for chips.
 */
class Dates
{
    /**
     * A Y-m-d date, or null when the value isn't one.
     */
    public static function parseDate(mixed $value): ?CarbonImmutable
    {
        return static::parse($value, ['Y-m-d']);
    }

    /**
     * A date and time (Y-m-d H:i, Y-m-d\TH:i or with seconds), or null when the value isn't one.
     */
    public static function parseDateTime(mixed $value): ?CarbonImmutable
    {
        return static::parse($value, ['Y-m-d H:i', 'Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s']);
    }

    /**
     * @param  array<int, string>  $formats
     */
    protected static function parse(mixed $value, array $formats): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        foreach ($formats as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!' . $format, $value);
            } catch (Throwable) {
                continue;
            }

            if ($date !== null && $date->format($format) === $value) {
                return $date;
            }
        }

        return null;
    }

    public static function formatDate(CarbonImmutable $date): string
    {
        return $date->locale(app()->getLocale())->translatedFormat((string) config('filters.date_format', 'j M Y'));
    }

    public static function formatDateTime(CarbonImmutable $date): string
    {
        return $date->locale(app()->getLocale())->translatedFormat((string) config('filters.datetime_format', 'j M Y H:i'));
    }
}
