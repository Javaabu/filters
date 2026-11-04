<?php

namespace Javaabu\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Javaabu\Stencil\Fields\Checkbox;

/**
 * A checkbox: when it's ticked, only rows where the column is true. Give
 * query() for anything else ("only mine", "has comments").
 */
class BooleanFilter extends Filter
{
    public function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $value = end($value);
        }

        return filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : null;
    }

    protected function defaultFields(): array
    {
        return [
            Checkbox::make($this->name)->label($this->getLabel()),
        ];
    }

    protected function applyToQuery(Builder $query, mixed $value): void
    {
        $query->where($this->qualifyColumn($query), true);
    }

    protected function formatChipValue(mixed $value): ?string
    {
        return null;
    }
}
