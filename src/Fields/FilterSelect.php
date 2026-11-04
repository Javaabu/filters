<?php

namespace Javaabu\Filters\Fields;

use Javaabu\Stencil\Fields\Select;

/**
 * A stencil select for the filter bar. It never adds javaabu/forms' sync_
 * hidden field (which would end up in the URL), and it keeps the labels of
 * its selected options when it searches a URL (ajax), so they still show.
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
        $props = [...parent::getFieldProps(), 'excludeSyncField' => true];

        if (($props['isAjax'] ?? false) && $this->selected_options !== []) {
            $props['options'] = $this->selected_options;
        }

        return $props;
    }
}
