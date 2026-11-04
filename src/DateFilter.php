<?php

namespace Javaabu\Filters;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use InvalidArgumentException;
use Javaabu\Filters\Support\Dates;
use Javaabu\Stencil\Fields\DatePicker;
use Javaabu\Stencil\Fields\Field;

/**
 * One date (Y-m-d): rows on that day, or before or after it with operator().
 */
class DateFilter extends Filter
{
    protected string $operator = '=';

    /**
     * Compare with =, <, <=, > or >=.
     */
    public function operator(string $operator): static
    {
        if (! in_array($operator, ['=', '<', '<=', '>', '>='], true)) {
            throw new InvalidArgumentException("Unknown date filter operator [{$operator}].");
        }

        $this->operator = $operator;

        return $this;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    protected function parse(mixed $value): ?CarbonImmutable
    {
        return Dates::parseDate($value);
    }

    protected function serialize(CarbonImmutable $date): string
    {
        return $date->format('Y-m-d');
    }

    protected function format(CarbonImmutable $date): string
    {
        return Dates::formatDate($date);
    }

    protected function picker(string $name): Field
    {
        return DatePicker::make($name);
    }

    public function normalizeValue(mixed $value): mixed
    {
        $date = $this->parse(static::clean($value));

        return $date === null ? null : $this->serialize($date);
    }

    protected function defaultFields(): array
    {
        return [
            $this->picker($this->name)->label($this->getLabel()),
        ];
    }

    protected function applyToQuery(Builder $query, mixed $value): void
    {
        $date = $this->parse($value);
        $column = $this->qualifyColumn($query);

        match ($this->operator) {
            '=' => $query->whereBetween($column, [$date->startOfDay(), $date->endOfDay()]),
            '<' => $query->where($column, '<', $date->startOfDay()),
            '<=' => $query->where($column, '<=', $date->endOfDay()),
            '>' => $query->where($column, '>', $date->endOfDay()),
            '>=' => $query->where($column, '>=', $date->startOfDay()),
        };
    }

    protected function formatChipValue(mixed $value): ?string
    {
        $formatted = $this->format($this->parse($value));

        return match ($this->operator) {
            '<', '<=' => __('filters::strings.until_value', ['value' => $formatted]),
            '>', '>=' => __('filters::strings.from_value', ['value' => $formatted]),
            default => $formatted,
        };
    }
}
