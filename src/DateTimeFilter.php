<?php

namespace Javaabu\Filters;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Javaabu\Filters\Support\Dates;
use Javaabu\Stencil\Fields\DateTimePicker;
use Javaabu\Stencil\Fields\Field;

/**
 * One date and time (Y-m-d H:i): rows in that minute, or before or after it with operator().
 */
class DateTimeFilter extends DateFilter
{
    protected function parse(mixed $value): ?CarbonImmutable
    {
        return Dates::parseDateTime($value);
    }

    protected function serialize(CarbonImmutable $date): string
    {
        return $date->format('Y-m-d H:i');
    }

    protected function format(CarbonImmutable $date): string
    {
        return Dates::formatDateTime($date);
    }

    protected function picker(string $name): Field
    {
        return DateTimePicker::make($name);
    }

    protected function applyToQuery(Builder $query, mixed $value): void
    {
        $date = $this->parse($value);
        $column = $this->qualifyColumn($query);

        match ($this->operator) {
            '=' => $query->whereBetween($column, [$date->startOfMinute(), $date->endOfMinute()]),
            '<' => $query->where($column, '<', $date->startOfMinute()),
            '<=' => $query->where($column, '<=', $date->endOfMinute()),
            '>' => $query->where($column, '>', $date->endOfMinute()),
            '>=' => $query->where($column, '>=', $date->startOfMinute()),
        };
    }
}
