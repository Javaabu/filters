<?php

namespace Javaabu\Filters;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * An applied filter, as shown above the results: "Status: Draft, Published",
 * with the URL that clears it.
 *
 * @implements Arrayable<string, string|null>
 */
final readonly class Chip implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $filter,
        public string $label,
        public ?string $value,
        public string $url,
    ) {}

    /**
     * The chip as one line of text: "Status: Draft".
     */
    public function text(): string
    {
        if ($this->value === null || $this->value === '') {
            return $this->label;
        }

        return __('filters::strings.chip', ['label' => $this->label, 'value' => $this->value]);
    }

    /**
     * @return array{filter: string, label: string, value: string|null, text: string, url: string}
     */
    public function toArray(): array
    {
        return [
            'filter' => $this->filter,
            'label' => $this->label,
            'value' => $this->value,
            'text' => $this->text(),
            'url' => $this->url,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
