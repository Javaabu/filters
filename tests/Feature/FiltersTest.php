<?php

namespace Javaabu\Filters\Tests\Feature;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Javaabu\Filters\Exceptions\FiltersException;
use Javaabu\Filters\Filter;
use Javaabu\Filters\Filters;
use Javaabu\Filters\SelectFilter;
use Javaabu\Filters\Sort;
use Javaabu\Filters\Tests\Feature\Concerns\SeedsArticles;
use Javaabu\Filters\Tests\Fixtures\Enums\ArticleStatuses;
use Javaabu\Filters\Tests\Fixtures\Filters\ArticleFilters;
use Javaabu\Filters\Tests\Fixtures\Models\Article;
use Javaabu\Filters\Tests\Fixtures\Models\Category;
use Javaabu\Filters\Tests\TestCase;
use Javaabu\Filters\TextFilter;
use Javaabu\Filters\TrashedFilter;
use Javaabu\Stencil\Fields\Checkbox;
use Javaabu\Stencil\Fields\TextInput;
use PHPUnit\Framework\Attributes\Test;

class FiltersTest extends TestCase
{
    use SeedsArticles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedArticles();
    }

    #[Test]
    public function it_defines_filters_inline_and_in_a_class(): void
    {
        $inline = Filters::make([
            TextFilter::make('search')->columns(['title']),
            null,
            SelectFilter::make('status')->options(['draft' => 'Draft']),
        ], ['title']);

        $this->assertSame(['search', 'status'], array_keys($inline->getFilters()));
        $this->assertSame(['title'], array_keys($inline->getSorts()));
        $this->assertSame($inline, $inline->getFilter('search')->getFilters());

        $class = ArticleFilters::make();

        $this->assertSame(Article::class, $class->getModel());
        $this->assertTrue($class->hasFilter('category_id'));
        $this->assertSame(['title', 'published_at', 'word_count', 'author'], array_keys($class->getSorts()));
        $this->assertArrayNotHasKey('author_id', $class->getVisibleFilters());
    }

    #[Test]
    public function it_reads_the_current_request_by_default(): void
    {
        $this->app->instance('request', Request::create('/articles', 'GET', ['search' => 'ވާރޭ']));

        $filters = ArticleFilters::make();

        $this->assertSame(['search' => 'ވާރޭ'], $filters->getValues());
        $this->assertSame(['rain'], $this->slugs($filters->apply(Article::query())));
    }

    #[Test]
    public function it_reads_a_request_or_query_parameters(): void
    {
        $filters = ArticleFilters::make()->fromRequest(Request::create('/articles', 'GET', ['status' => ['draft']]));

        $this->assertSame(['status' => ['draft']], $filters->getValues());

        $filters->fromQuery(['status' => ['published', 'archived']]);

        $this->assertSame(['status' => ['published', 'archived']], $filters->getValues());
        $this->assertTrue($filters->isActive('status'));
        $this->assertFalse($filters->isActive('search'));
        $this->assertSame(1, $filters->countActiveFilters());
        $this->assertSame(['status'], array_keys($filters->getActiveFilters()));
    }

    #[Test]
    public function it_ignores_empty_and_unknown_parameters(): void
    {
        $filters = ArticleFilters::make()->fromQuery([
            'search' => '   ',
            'status' => ['', null],
            'category_id' => '',
            'unknown' => 'x',
            'page' => '2',
        ]);

        $this->assertSame([], $filters->getValues());
        $this->assertFalse($filters->hasActiveFilters());
    }

    #[Test]
    public function it_applies_through_the_builder_macro(): void
    {
        $filters = ArticleFilters::make()->fromQuery(['status' => 'draft']);

        $this->assertSame(['draft'], $this->slugs(Article::query()->applyFilters($filters)));
    }

    #[Test]
    public function it_applies_to_relationship_queries(): void
    {
        $category = Article::firstWhere('slug', 'rain')->category;

        $filters = Filters::make([
            SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
            TextFilter::make('search')->columns(['title']),
        ])->fromQuery(['status' => 'published', 'search' => 'ވާރޭ']);

        $this->assertSame(['rain'], $this->slugs($filters->apply(Category::find($category->id)->hasMany(Article::class))));
    }

    #[Test]
    public function it_remembers_the_model_from_the_query(): void
    {
        $filters = Filters::make([TextFilter::make('search')->columns(['title'])]);

        $this->assertNull($filters->getModel());

        $filters->apply(Article::query());

        $this->assertSame(Article::class, $filters->getModel());
    }

    #[Test]
    public function it_applies_defaults_only_when_none_of_the_filters_parameters_are_present(): void
    {
        $make = fn (array $query) => Filters::make([
            SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->default('published'),
        ])->fromQuery($query);

        $this->assertSame(['status' => 'published'], $make([])->getValues());
        $this->assertSame([], $make(['status' => ''])->getValues());
        $this->assertSame(['status' => 'draft'], $make(['status' => 'draft'])->getValues());
    }

    #[Test]
    public function it_accepts_enum_defaults(): void
    {
        $filters = Filters::make([
            SelectFilter::make('status')->options(ArticleStatuses::class)->multiple()
                ->default([ArticleStatuses::DRAFT]),
        ])->fromQuery([]);

        $this->assertSame(['status' => ['draft']], $filters->getValues());
    }

    #[Test]
    public function it_applies_custom_filters_with_several_fields(): void
    {
        $filter = Filter::make('words')
            ->schema([
                TextInput::make('min_words')->numeric(),
                Checkbox::make('featured_only'),
            ])
            ->query(function (Builder $query, array $value, Filter $filter) {
                $this->assertSame('words', $filter->getName());

                $query->where('word_count', '>=', (int) ($value['min_words'] ?? 0));

                if (isset($value['featured_only'])) {
                    $query->where('is_featured', true);
                }
            });

        $filters = Filters::make([$filter])->fromQuery(['min_words' => '100', 'featured_only' => '1']);

        $this->assertSame(['min_words', 'featured_only'], $filter->getQueryKeys());
        $this->assertSame(['words' => ['min_words' => '100', 'featured_only' => '1']], $filters->getValues());
        $this->assertSame(['rain'], $this->slugs($filters->apply(Article::query())));
        $this->assertSame(['min_words' => '100', 'featured_only' => '1'], $filters->getFilterParameters());
    }

    #[Test]
    public function it_applies_a_plain_filter_to_its_column(): void
    {
        $filters = Filters::make([Filter::make('slug')])->fromQuery(['slug' => 'final']);

        $this->assertSame(['final'], $this->slugs($filters->apply(Article::query())));

        $filters->fromQuery(['slug' => ['final', 'rain']]);

        $this->assertEqualsCanonicalizing(['final', 'rain'], $this->slugs($filters->apply(Article::query())));
    }

    #[Test]
    public function it_needs_a_query_for_a_custom_filter_with_several_fields(): void
    {
        $filters = Filters::make([
            Filter::make('range')->schema([TextInput::make('a'), TextInput::make('b')]),
        ])->fromQuery(['a' => '1']);

        $this->expectException(FiltersException::class);
        $this->expectExceptionMessage('needs ->query(');

        $filters->apply(Article::query());
    }

    #[Test]
    public function it_rejects_duplicate_names_and_non_filters(): void
    {
        $this->expectException(FiltersException::class);
        $this->expectExceptionMessage('two filters named [search]');

        Filters::make([TextFilter::make(), TextFilter::make()])->getFilters();
    }

    #[Test]
    public function it_rejects_things_that_arent_filters(): void
    {
        $this->expectException(FiltersException::class);

        Filters::make([Sort::make('title')])->getFilters();
    }

    #[Test]
    public function it_needs_a_name_for_types_without_a_default_one(): void
    {
        $this->expectException(FiltersException::class);
        $this->expectExceptionMessage('SelectFilter::make(\'name\')');

        SelectFilter::make();
    }

    #[Test]
    public function it_generates_labels_from_names(): void
    {
        $this->assertSame('Category', SelectFilter::make('category_id')->getLabel());
        $this->assertSame('Published At', Filter::make('published_at')->getLabel());
        $this->assertSame('Search', TextFilter::make()->getLabel());
        $this->assertSame('Keywords', TextFilter::make()->label('Keywords')->getLabel());
        $this->assertSame('Deleted', TrashedFilter::make()->getLabel());
    }

    #[Test]
    public function it_hides_filters_from_the_bar_with_a_condition(): void
    {
        $filter = Filter::make('slug')->visible(fn () => false);

        $this->assertTrue($filter->isHidden());
        $this->assertFalse(Filter::make('slug')->hidden(false)->isHidden());
    }

    #[Test]
    public function it_injects_filters_and_the_model_into_closures(): void
    {
        $filters = ArticleFilters::make()->withFilters([
            Filter::make('custom')->label(fn (?string $model, Filters $filters) => class_basename($model) . ':' . $filters->getId()),
        ]);

        $this->assertSame('Article:filter', $filters->getFilter('custom')->getLabel());
    }
}
