<?php

namespace Javaabu\Filters;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Javaabu\Stencil\Fields\Select;

/**
 * Deleted rows of a soft-deleting model: without them (default), with them, or only them.
 */
class TrashedFilter extends Filter
{
    public const string WITH = 'with';

    public const string ONLY = 'only';

    public static function getDefaultName(): string
    {
        return 'trashed';
    }

    public function getLabel(): string
    {
        return filled($this->evaluate($this->label)) ? parent::getLabel() : __('filters::strings.trashed');
    }

    /**
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        return [
            self::WITH => __('filters::strings.with_trashed'),
            self::ONLY => __('filters::strings.only_trashed'),
        ];
    }

    public function normalizeValue(mixed $value): mixed
    {
        return is_string($value) && array_key_exists($value, $this->getOptions()) ? $value : null;
    }

    protected function defaultFields(): array
    {
        return [
            Select::make($this->name)
                ->label($this->getLabel())
                ->options($this->getOptions())
                ->placeholder(__('filters::strings.without_trashed')),
        ];
    }

    protected function applyToQuery(Builder $query, mixed $value): void
    {
        $value === self::ONLY ? $query->onlyTrashed() : $query->withTrashed();
    }

    protected function formatChipValue(mixed $value): ?string
    {
        return $this->getOptions()[$value] ?? null;
    }
}
