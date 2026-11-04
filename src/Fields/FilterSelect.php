<?php

namespace Javaabu\Filters\Fields;

use Javaabu\Stencil\Fields\Select;

/**
 * A stencil select that keeps the labels of its selected options when it
 * searches a URL (ajax), so the chosen values show in the filter bar.
 */
class FilterSelect extends Select
{
    /** @var array<int|string, string> */
    protected array $selected_options = [];

    /**
     * @param  array<int|string, string>  $options
     */
    public function selectedOptions(array $options): static
    {
        $this->selected_options = $options;

        return $this;
    }

    protected function getFieldProps(): array
    {
        $props = parent::getFieldProps();

        if (($props['isAjax'] ?? false) && $this->selected_options !== []) {
            $props['options'] = $this->selected_options;
        }

        return $props;
    }
}
