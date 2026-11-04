@props([
    'filters',
    'sort',
    'label' => null,
])

@php
    /** @var \Javaabu\Filters\Filters $filters */
    $sortable = $filters->hasSort($sort);
    $active = $sortable && $filters->isSortedBy($sort);
    $direction = $filters->getDirection();
    $text = $label ?? ($filters->getSorts()[$sort] ?? null)?->getLabel() ?? $sort;
@endphp

@if(! $sortable)
    <x-forms::table.heading :label="$text" :attributes="$attributes">{{ $slot }}</x-forms::table.heading>
@else
    <x-forms::table.heading :attributes="$attributes->merge([
        'aria-sort' => $active ? ($direction === 'desc' ? 'descending' : 'ascending') : 'none',
        'data-filter-sort-heading' => $sort,
    ])">
        <a
            href="{{ $filters->sortUrl($sort) }}"
            class="inline-flex items-center gap-1 font-medium hover:text-foreground"
            aria-label="{{ __('forms::strings.sort_by', ['label' => $text]) }}"
        >
            @if($slot->isNotEmpty())
                {{ $slot }}
            @else
                {{ $text }}
            @endif
            <x-forms::icon
                :name="$active ? ($direction === 'desc' ? 'sort_desc' : 'sort_asc') : 'sort'"
                @class(['ms-1', 'opacity-50' => ! $active])
            />
        </a>
    </x-forms::table.heading>
@endif
