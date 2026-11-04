<?php

namespace Javaabu\Filters;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Javaabu\Stencil\Fields\Select;

/**
 * Yes, no or all: a boolean column (featured), or with nullable() whether a
 * column is filled (verified for email_verified_at). Give queries() for anything else.
 */
class TernaryFilter extends Filter
{
    protected bool $is_nullable = false;

    protected string|Closure|null $true_label = null;

    protected string|Closure|null $false_label = null;

    protected string|Closure|null $placeholder = null;

    protected ?Closure $true_query = null;

    protected ?Closure $false_query = null;

    /**
     * Yes means the column isn't null, no means it is.
     */
    public function nullable(bool $condition = true): static
    {
        $this->is_nullable = $condition;

        return $this;
    }

    public function trueLabel(string|Closure|null $label): static
    {
        $this->true_label = $label;

        return $this;
    }

    public function falseLabel(string|Closure|null $label): static
    {
        $this->false_label = $label;

        return $this;
    }

    public function placeholder(string|Closure|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * What yes and no do: fn (Builder $query).
     */
    public function queries(?Closure $true = null, ?Closure $false = null): static
    {
        $this->true_query = $true;
        $this->false_query = $false;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        return [
            '1' => (string) ($this->evaluate($this->true_label) ?? __('filters::strings.yes')),
            '0' => (string) ($this->evaluate($this->false_label) ?? __('filters::strings.no')),
        ];
    }

    public function normalizeValue(mixed $value): mixed
    {
        if (! is_scalar($value) || $value === '') {
            return null;
        }

        $bool = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($bool === null) {
            return null;
        }

        return $bool ? '1' : '0';
    }

    protected function defaultFields(): array
    {
        return [
            Select::make($this->name)
                ->label($this->getLabel())
                ->options($this->getOptions())
                ->placeholder((string) ($this->evaluate($this->placeholder) ?? __('filters::strings.all'))),
        ];
    }

    protected function applyToQuery(Builder $query, mixed $value): void
    {
        $is_true = $value === '1';
        $callback = $is_true ? $this->true_query : $this->false_query;

        if ($callback !== null) {
            $this->evaluate($callback, ['query' => $query, 'value' => $is_true], [Builder::class => $query]);

            return;
        }

        $column = $this->qualifyColumn($query);

        if ($this->is_nullable) {
            $is_true ? $query->whereNotNull($column) : $query->whereNull($column);

            return;
        }

        $query->where($column, $is_true);
    }

    protected function formatChipValue(mixed $value): ?string
    {
        return $this->getOptions()[$value] ?? null;
    }
}
