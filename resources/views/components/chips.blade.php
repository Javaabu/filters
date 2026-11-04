@props([
    'filters',
])

@php
    /** @var \Javaabu\Filters\Filters $filters */
    $chips = $filters->chips();
@endphp

@if($chips)
    <div
        {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}
        role="list"
        aria-label="{{ __('filters::strings.applied_filters') }}"
        data-filter-chips
    >
        @foreach($chips as $chip)
            <span
                class="inline-flex max-w-full items-center gap-1 rounded-full border bg-muted ps-2.5 pe-1 py-0.5 text-sm text-foreground"
                role="listitem"
                data-filter-chip="{{ $chip->filter }}"
            >
                <span class="truncate">
                    <span class="text-muted-foreground">{{ $chip->label }}</span>@if(filled($chip->value))<span class="text-muted-foreground">:</span>
                    <bdi>{{ $chip->value }}</bdi>@endif
                </span>
                <a
                    href="{{ $chip->url }}"
                    class="inline-flex size-5 shrink-0 items-center justify-center rounded-full text-muted-foreground hover:bg-accent hover:text-accent-foreground focus-visible:outline-2 focus-visible:outline-ring"
                    aria-label="{{ __('filters::strings.remove', ['filter' => $chip->text()]) }}"
                    data-filter-chip-remove
                >
                    <x-forms::icon name="clear" />
                </a>
            </span>
        @endforeach

        @if(count($chips) > 1)
            <a
                href="{{ $filters->clearUrl() }}"
                class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                data-filter-chips-clear
            >
                {{ __('filters::strings.clear_all') }}
            </a>
        @endif
    </div>
@endif
