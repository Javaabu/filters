<?php

namespace Javaabu\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;

class FiltersServiceProvider extends ServiceProvider
{
    use RegistersAssets;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/filters.php', 'filters');

        $this->registerBuilderMacro();
    }

    /**
     * Article::query()->applyFilters($filters): the same as $filters->apply($query).
     */
    protected function registerBuilderMacro(): void
    {
        if (Builder::hasGlobalMacro('applyFilters')) {
            return;
        }

        Builder::macro('applyFilters', function (Filters $filters) {
            /** @var Builder $this */
            return $filters->apply($this);
        });
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'filters');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'filters');

        $this->registerAssets('filters', __DIR__ . '/../resources', fn (Package $package) => $package
            ->js('js')
            ->npm([
                '@javaabu/forms' => 'github:Javaabu/react-forms#next-gen',
            ], [
                'react' => '^19.0',
                'react-dom' => '^19.0',
            ]));

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/filters.php' => config_path('filters.php'),
        ], 'filters-config');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/filters'),
        ], 'filters-translations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/filters'),
        ], 'filters-views');
    }
}
