<?php

namespace Javaabu\Filters;

use Javaabu\Filters\Support\Dates;
use Javaabu\Stencil\Fields\DatePicker;
use Javaabu\Stencil\Fields\DateTimePicker;
use Javaabu\Stencil\Fields\Field;

/**
 * Rows between two dates (published_at_from, published_at_to), each day
 * included. withTime() for dates and times.
 */
class DateRangeFilter extends RangeFilter
{
    protected bool $with_time = false;

    /**
     * Pick dates and times (Y-m-d H:i) instead of dates.
     */
    public function withTime(bool $condition = true): static
    {
        $this->with_time = $condition;

        return $this;
    }

    protected function fromSuffix(): string
    {
        return 'from';
    }

    protected function toSuffix(): string
    {
        return 'to';
    }

    protected function normalizeBound(mixed $value): ?string
    {
        if ($this->with_time) {
            return Dates::parseDateTime($value)?->format('Y-m-d H:i');
        }

        return Dates::parseDate($value)?->format('Y-m-d');
    }

    protected function field(string $name): Field
    {
        return $this->with_time ? DateTimePicker::make($name) : DatePicker::make($name);
    }

    protected function lowerBound(string $value): mixed
    {
        return $this->with_time ? Dates::parseDateTime($value)->startOfMinute() : Dates::parseDate($value)->startOfDay();
    }

    protected function upperBound(string $value): mixed
    {
        return $this->with_time ? Dates::parseDateTime($value)->endOfMinute() : Dates::parseDate($value)->endOfDay();
    }

    protected function formatBound(string $value): string
    {
        return $this->with_time
            ? Dates::formatDateTime(Dates::parseDateTime($value))
            : Dates::formatDate(Dates::parseDate($value));
    }
}
