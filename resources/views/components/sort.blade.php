@props([
    'filters',
])

@php
    /** @var \Javaabu\Filters\Filters $filters */
    $options = $filters->getSortOptions();
@endphp

@if($options)
    <div {{ $attributes->merge(['class' => 'grid gap-x-4 sm:grid-cols-2 lg:grid-cols-4']) }} data-filter-sort>
        <x-forms::select
            :name="$filters->getSortParameter()"
            :label="__('filters::strings.sort_by')"
            :options="$options"
            :default="$filters->getSortName()"
        />

        <x-forms::select
            :name="$filters->getDirectionParameter()"
            :label="__('filters::strings.direction')"
            :options="['ASC' => __('filters::strings.ascending'), 'DESC' => __('filters::strings.descending')]"
            :default="strtoupper($filters->getDirection())"
        />
    </div>
@endif
