<?php

namespace Javaabu\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Javaabu\Stencil\Components\Group;
use Javaabu\Stencil\Fields\Field;

/**
 * A filter with a lower and an upper bound, each its own query parameter
 * ({name}_from and {name}_to by default). Either bound alone applies.
 */
abstract class RangeFilter extends Filter
{
    protected ?string $from_parameter = null;

    protected ?string $to_parameter = null;

    abstract protected function fromSuffix(): string;

    abstract protected function toSuffix(): string;

    /**
     * The query parameters of the two bounds, when they aren't {name}_from and {name}_to.
     */
    public function parameters(string $from, string $to): static
    {
        $this->from_parameter = $from;
        $this->to_parameter = $to;

        return $this;
    }

    public function getFromParameter(): string
    {
        return $this->from_parameter ?? $this->name . '_' . $this->fromSuffix();
    }

    public function getToParameter(): string
    {
        return $this->to_parameter ?? $this->name . '_' . $this->toSuffix();
    }

    public function getQueryKeys(): array
    {
        return [$this->getFromParameter(), $this->getToParameter()];
    }

    /**
     * A bound from the query string, cleaned, or null when it isn't valid.
     */
    abstract protected function normalizeBound(mixed $value): ?string;

    /**
     * @param  array<string, mixed>  $input
     * @param  array<int, string>  $keys
     */
    protected function readInput(array $input, array $keys): mixed
    {
        return [
            'from' => $input[$this->getFromParameter()] ?? null,
            'to' => $input[$this->getToParameter()] ?? null,
        ];
    }

    /**
     * @return array{from?: string, to?: string}|null
     */
    public function normalizeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return null;
        }

        $range = array_filter([
            'from' => $this->normalizeBound(static::clean($value['from'] ?? null)),
            'to' => $this->normalizeBound(static::clean($value['to'] ?? null)),
        ], fn ($bound) => $bound !== null);

        return $range === [] ? null : $range;
    }

    public function toQueryParameters(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        return array_filter([
            $this->getFromParameter() => $value['from'] ?? null,
            $this->getToParameter() => $value['to'] ?? null,
        ], fn ($bound) => $bound !== null);
    }

    abstract protected function field(string $name): Field;

    protected function defaultFields(): array
    {
        return [
            $this->field($this->getFromParameter())->label(__('filters::strings.range_from', ['label' => $this->getLabel()])),
            $this->field($this->getToParameter())->label(__('filters::strings.range_to', ['label' => $this->getLabel()])),
        ];
    }

    protected function defaultComponents(): array
    {
        return [
            Group::make($this->getFields())->columns(2),
        ];
    }

    protected function defaultColumnSpan(): int|string|array|null
    {
        return ['default' => 'full', 'lg' => 2];
    }

    protected function applyToQuery(Builder $query, mixed $value): void
    {
        $column = $this->qualifyColumn($query);

        if (isset($value['from'])) {
            $query->where($column, '>=', $this->lowerBound($value['from']));
        }

        if (isset($value['to'])) {
            $query->where($column, '<=', $this->upperBound($value['to']));
        }
    }

    abstract protected function lowerBound(string $value): mixed;

    abstract protected function upperBound(string $value): mixed;

    abstract protected function formatBound(string $value): string;

    protected function formatChipValue(mixed $value): ?string
    {
        $from = isset($value['from']) ? $this->formatBound($value['from']) : null;
        $to = isset($value['to']) ? $this->formatBound($value['to']) : null;

        if ($from !== null && $to !== null) {
            return __('filters::strings.range', ['from' => $from, 'to' => $to]);
        }

        if ($from !== null) {
            return __($this->fromChipKey(), ['value' => $from]);
        }

        return __($this->toChipKey(), ['value' => $to]);
    }

    protected function fromChipKey(): string
    {
        return 'filters::strings.from_value';
    }

    protected function toChipKey(): string
    {
        return 'filters::strings.until_value';
    }
}
