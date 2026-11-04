@props([
    'filters',
    'sort' => false,
    'chips' => true,
    'export' => false,
])

@php
    /** @var \Javaabu\Filters\Filters $filters */
    $hidden = $filters->getHiddenParameters();

    if ($sort) {
        unset($hidden[$filters->getSortParameter()], $hidden[$filters->getDirectionParameter()]);
    }
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3']) }} data-filters="{{ $filters->getId() }}">
    <form
        method="GET"
        action="{{ $filters->getAction() }}"
        id="{{ $filters->getId() }}"
        role="search"
        aria-label="{{ __('filters::strings.filters') }}"
        data-filters-form
    >
        <x-forms::card>
            {{ $filters->form()->render('blade') }}

            @if($sort)
                <x-filters::sort :filters="$filters" />
            @endif

            @foreach($hidden as $name => $value)
                @foreach((array) $value as $item)
                    <input type="hidden" name="{{ is_array($value) ? $name . '[]' : $name }}" value="{{ $item }}" />
                @endforeach
            @endforeach

            <x-forms::filter-submit :cancel-url="$filters->resetUrl()" :export="$export">
                {{ $slot }}
            </x-forms::filter-submit>
        </x-forms::card>
    </form>

    @if($chips)
        <x-filters::chips :filters="$filters" />
    @endif
</div>
