<?php

namespace Javaabu\Filters;

use Javaabu\Stencil\Fields\Field;
use Javaabu\Stencil\Fields\TextInput;

/**
 * Rows with a number between a minimum and a maximum (word_count_min, word_count_max).
 */
class NumberRangeFilter extends RangeFilter
{
    protected bool $is_integer = false;

    protected int|float|null $step = null;

    /**
     * Only whole numbers.
     */
    public function integer(bool $condition = true): static
    {
        $this->is_integer = $condition;

        return $this;
    }

    public function step(int|float|null $step): static
    {
        $this->step = $step;

        return $this;
    }

    protected function fromSuffix(): string
    {
        return 'min';
    }

    protected function toSuffix(): string
    {
        return 'max';
    }

    protected function normalizeBound(mixed $value): ?string
    {
        if (! is_string($value) || ! is_numeric($value)) {
            return null;
        }

        if ($this->is_integer) {
            return filter_var($value, FILTER_VALIDATE_INT) === false ? null : (string) (int) $value;
        }

        return (string) ($value + 0);
    }

    protected function field(string $name): Field
    {
        $field = TextInput::make($name)->numeric();

        if ($this->is_integer) {
            $field->integer();
        }

        if ($this->step !== null) {
            $field->step($this->step);
        }

        return $field;
    }

    protected function lowerBound(string $value): mixed
    {
        return $value + 0;
    }

    protected function upperBound(string $value): mixed
    {
        return $value + 0;
    }

    protected function formatBound(string $value): string
    {
        return $value;
    }

    protected function fromChipKey(): string
    {
        return 'filters::strings.min_value';
    }

    protected function toChipKey(): string
    {
        return 'filters::strings.max_value';
    }
}
