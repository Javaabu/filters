<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | What $filters->render() returns: 'blade' renders the filter bar as HTML
    | with javaabu/forms, 'inertia' returns the filters as an array for the
    | React components in @javaabu/filters. null follows config('stencil.views').
    |
    */

    'views' => env('FILTERS_VIEWS'),

    /*
    |--------------------------------------------------------------------------
    | Query string
    |--------------------------------------------------------------------------
    |
    | The parameters that hold the sort. They match what javaabu/forms'
    | sortable table headings (<x-forms::table.heading>, TableHeading) set.
    |
    */

    'query' => [
        'sort' => 'orderby',

        'direction' => 'order',

        // Parameters kept when filters are submitted or cleared, like the page size.
        'preserve' => ['per_page'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Formats
    |--------------------------------------------------------------------------
    |
    | How dates in applied-filter chips are shown, translated to the app
    | locale with Carbon's translatedFormat().
    |
    */

    'date_format' => 'j M Y',

    'datetime_format' => 'j M Y H:i',

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Grid columns of the filter bar, per breakpoint.
    |
    */

    'columns' => ['default' => 1, 'sm' => 2, 'lg' => 4],

];
