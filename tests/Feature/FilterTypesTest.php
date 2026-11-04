<?php

namespace Javaabu\Filters\Tests\Feature;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Javaabu\Filters\BooleanFilter;
use Javaabu\Filters\DateFilter;
use Javaabu\Filters\DateRangeFilter;
use Javaabu\Filters\DateTimeFilter;
use Javaabu\Filters\Exceptions\FiltersException;
use Javaabu\Filters\Filters;
use Javaabu\Filters\MultiSelectFilter;
use Javaabu\Filters\NumberRangeFilter;
use Javaabu\Filters\SelectFilter;
use Javaabu\Filters\TernaryFilter;
use Javaabu\Filters\Tests\Feature\Concerns\SeedsArticles;
use Javaabu\Filters\Tests\Fixtures\Enums\ArticleStatuses;
use Javaabu\Filters\Tests\Fixtures\Filters\ArticleFilters;
use Javaabu\Filters\Tests\Fixtures\Models\Article;
use Javaabu\Filters\Tests\Fixtures\Models\Category;
use Javaabu\Filters\Tests\Fixtures\Models\Page;
use Javaabu\Filters\Tests\Fixtures\Models\Tag;
use Javaabu\Filters\Tests\TestCase;
use Javaabu\Filters\TextFilter;
use Javaabu\Filters\TrashedFilter;
use PHPUnit\Framework\Attributes\Test;

class FilterTypesTest extends TestCase
{
    use SeedsArticles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedArticles();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, string>
     */
    protected function filter(array $query, ?Filters $filters = null): array
    {
        $filters ??= ArticleFilters::make();

        return $this->slugs($filters->fromQuery($query)->apply(Article::query()->reorder()));
    }

    /**
     * @return array<string, mixed>
     */
    protected function props(Filters $filters, string $field): array
    {
        return $filters->form()->toNode()->find($field)->props;
    }

    /*
    |--------------------------------------------------------------------------
    | Text
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_searches_columns_and_related_columns(): void
    {
        $this->assertSame(['rain'], $this->filter(['search' => 'ވާރޭ']));
        $this->assertEqualsCanonicalizing(['rain'], $this->filter(['search' => 'ޢާއިޝާ']));
        $this->assertEqualsCanonicalizing(['final', 'draft'], $this->filter(['search' => 'ahmed']));
    }

    #[Test]
    public function it_escapes_like_wildcards(): void
    {
        $this->assertSame(['draft'], $this->filter(['search' => '100%']));
        $this->assertSame(['draft'], $this->filter(['search' => 'draft_']));
        $this->assertSame([], $this->filter(['search' => '%_%']));
    }

    #[Test]
    public function it_matches_with_operators(): void
    {
        $make = fn (string $operator) => Filters::make([TextFilter::make('slug')->operator($operator)]);

        $this->assertSame(['final'], $this->filter(['slug' => 'fin'], $make(TextFilter::STARTS_WITH)));
        $this->assertSame(['rain'], $this->filter(['slug' => 'ain'], $make(TextFilter::ENDS_WITH)));
        $this->assertSame([], $this->filter(['slug' => 'fin'], $make(TextFilter::EXACT)));
        $this->assertSame(['final'], $this->filter(['slug' => 'final'], $make(TextFilter::EXACT)));
        $this->assertEqualsCanonicalizing(['final', 'rain'], $this->filter(['slug' => 'in'], $make(TextFilter::CONTAINS)));
    }

    #[Test]
    public function it_rejects_unknown_operators(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TextFilter::make()->operator('regex');
    }

    #[Test]
    public function it_uses_the_models_search_scope_without_columns(): void
    {
        Page::create(['title' => 'ތަޢާރަފް', 'slug' => 'about']);
        Page::create(['title' => 'Contact', 'slug' => 'contact']);

        $filters = Filters::make([TextFilter::make()])->fromQuery(['search' => 'about']);

        $this->assertSame(['about'], $filters->apply(Page::query())->pluck('slug')->all());

        $filters->fromQuery(['search' => 'ތަޢާ']);

        $this->assertSame(['about'], $filters->apply(Page::query())->pluck('slug')->all());
    }

    #[Test]
    public function it_ignores_arrays_and_trims_and_limits_text(): void
    {
        $filter = TextFilter::make()->maxLength(5);

        $this->assertNull($filter->normalizeValue(['a']));
        $this->assertSame('ވާރޭގ', $filter->normalizeValue('  ވާރޭގައި '));
    }

    #[Test]
    public function it_renders_a_thaana_text_input(): void
    {
        $props = $this->props(ArticleFilters::make(), 'search');

        $this->assertTrue($props['thaana']);
        $this->assertSame('Search...', $props['placeholder']);
    }

    /*
    |--------------------------------------------------------------------------
    | Select
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_filters_by_enum_options_and_ignores_values_that_arent_options(): void
    {
        $this->assertEqualsCanonicalizing(['rain', 'final'], $this->filter(['status' => ['published']]));
        $this->assertEqualsCanonicalizing(['rain', 'final', 'draft'], $this->filter(['status' => ['published', 'draft', 'hacked']]));
        $this->assertSame(['status' => ['draft']], ArticleFilters::make()->fromQuery(['status' => ['hacked', 'draft']])->getValues());
        $this->assertSame([], ArticleFilters::make()->fromQuery(['status' => ['hacked']])->getValues());
    }

    #[Test]
    public function it_takes_one_value_for_a_single_select(): void
    {
        $filters = Filters::make([SelectFilter::make('status')->options(ArticleStatuses::class)]);

        $this->assertSame(['status' => 'draft'], $filters->fromQuery(['status' => 'draft'])->getValues());
        $this->assertSame(['status' => 'draft'], $filters->fromQuery(['status' => ['draft', 'published']])->getValues());
        $this->assertSame(['draft'], $this->filter(['status' => 'draft'], $filters));
    }

    #[Test]
    public function it_filters_by_static_and_closure_options(): void
    {
        $filters = Filters::make([
            SelectFilter::make('slug')->options(['rain' => 'Rain', 'final' => 'Final']),
            MultiSelectFilter::make('word_count')->options(fn () => collect([50 => 'Short', 1500 => 'Long'])),
        ]);

        $this->assertSame(['rain'], $this->filter(['slug' => 'rain'], $filters));
        $this->assertSame([], $filters->fromQuery(['slug' => 'draft'])->getValues());
        $this->assertEqualsCanonicalizing(['draft', 'final'], $this->filter(['word_count' => ['50', '1500']], $filters));
        $this->assertTrue($filters->getFilter('word_count')->isMultiple());
    }

    #[Test]
    public function it_filters_by_a_belongs_to_relationship(): void
    {
        $news = Category::firstWhere('name', 'ޚަބަރު');

        $this->assertEqualsCanonicalizing(['rain', 'draft'], $this->filter(['category_id' => (string) $news->id]));
        $this->assertSame([], ArticleFilters::make()->fromQuery(['category_id' => '999'])->getValues());
        $this->assertEquals(
            [$news->id => 'ޚަބަރު', $news->id + 1 => 'ކުޅިވަރު'],
            ArticleFilters::make()->getFilter('category_id')->getOptions() + [],
        );
    }

    #[Test]
    public function it_filters_by_a_belongs_to_many_relationship(): void
    {
        $world = Tag::firstWhere('name', 'World');
        $local = Tag::firstWhere('name', 'ރާއްޖެ');

        $this->assertSame(['final'], $this->filter(['tags' => [(string) $world->id]]));
        $this->assertEqualsCanonicalizing(['rain', 'final'], $this->filter(['tags' => [(string) $world->id, (string) $local->id]]));
    }

    #[Test]
    public function it_modifies_the_relationship_query(): void
    {
        $filter = SelectFilter::make('category_id')->relationship('category', 'name', fn (Builder $query) => $query->where('name', 'ކުޅިވަރު'));

        $filters = Filters::make([$filter])->model(Article::class);

        $this->assertSame(['ކުޅިވަރު'], array_values($filters->getFilter('category_id')->getOptions()));
    }

    #[Test]
    public function it_needs_a_model_for_relationship_options(): void
    {
        $filters = Filters::make([SelectFilter::make('category_id')->relationship('category')]);

        $this->expectException(FiltersException::class);
        $this->expectExceptionMessage('need a model');

        $filters->getFilter('category_id')->getOptions();
    }

    #[Test]
    public function it_filters_with_remote_options_and_resolves_their_labels(): void
    {
        $filters = Filters::make([
            SelectFilter::make('category_id')->relationship('category', 'name')->ajax('/api/categories'),
        ])->model(Article::class);

        $news = Category::firstWhere('name', 'ޚަބަރު');

        $this->assertEqualsCanonicalizing(['rain', 'draft'], $this->filter(['category_id' => (string) $news->id], $filters));
        $this->assertSame([], $filters->fromQuery(['category_id' => '999'])->getValues());
        $this->assertSame([], $filters->getFilter('category_id')->getOptions());

        $filters->fromQuery(['category_id' => (string) $news->id]);

        $props = $this->props($filters, 'category_id');

        $this->assertTrue($props['isAjax']);
        $this->assertSame('/api/categories', $props['ajaxUrl']);
        $this->assertSame([$news->id => 'ޚަބަރު'], $props['options']);
    }

    #[Test]
    public function it_resolves_remote_labels_with_a_callback_or_accepts_any_value(): void
    {
        $with_labels = SelectFilter::make('slug')->ajax('/api/articles')
            ->getOptionLabelsUsing(fn (array $values) => Article::whereIn('slug', $values)->pluck('title', 'slug')->all());

        $this->assertSame('final', $with_labels->normalizeValue('final'));
        $this->assertNull($with_labels->normalizeValue('missing'));
        $this->assertSame('Football final', $with_labels->getChipValue('final'));

        $any = SelectFilter::make('slug')->ajax('/api/articles');

        $this->assertSame('anything', $any->normalizeValue('anything'));
    }

    #[Test]
    public function it_renders_select_fields(): void
    {
        $filters = ArticleFilters::make();

        $this->assertTrue($this->props($filters, 'status')['multiple']);
        $this->assertSame('All', $this->props($filters, 'category_id')['placeholder']);
        $this->assertSame('Choose', SelectFilter::make('x')->placeholder('Choose')->getPlaceholder());
        $this->assertTrue(SelectFilter::make('x')->searchable()->options(['a' => 'A'])->getFields()[0]->isSearchable());
    }

    /*
    |--------------------------------------------------------------------------
    | Boolean and ternary
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_applies_a_boolean_filter_when_checked(): void
    {
        $this->assertEqualsCanonicalizing(['final'], $this->filter(['long' => '1']));
        $this->assertEqualsCanonicalizing(['rain', 'final', 'draft'], $this->filter(['long' => '0']));

        $featured = Filters::make([BooleanFilter::make('is_featured')]);

        $this->assertSame(['rain'], $this->filter(['is_featured' => 'on'], $featured));
        $this->assertSame(['rain'], $this->filter(['is_featured' => ['0', '1']], $featured));
        $this->assertNull(BooleanFilter::make('x')->getChipValue('1'));
    }

    #[Test]
    public function it_applies_a_ternary_filter(): void
    {
        $this->assertSame(['rain'], $this->filter(['is_featured' => '1']));
        $this->assertEqualsCanonicalizing(['final', 'draft'], $this->filter(['is_featured' => '0']));
        $this->assertEqualsCanonicalizing(['rain', 'final', 'draft'], $this->filter(['is_featured' => 'maybe']));
        $this->assertSame(['rain'], $this->filter(['reviewed' => 'true']));
        $this->assertEqualsCanonicalizing(['final', 'draft'], $this->filter(['reviewed' => 'false']));
    }

    #[Test]
    public function it_applies_ternary_queries(): void
    {
        $filters = Filters::make([
            TernaryFilter::make('long')->queries(
                true: fn (Builder $query) => $query->where('word_count', '>=', 1000),
                false: fn (Builder $query) => $query->where('word_count', '<', 1000),
            )->trueLabel('Long')->falseLabel('Short')->placeholder('Any length'),
        ]);

        $this->assertSame(['final'], $this->filter(['long' => '1'], $filters));
        $this->assertEqualsCanonicalizing(['rain', 'draft'], $this->filter(['long' => '0'], $filters));
        $this->assertSame(['1' => 'Long', '0' => 'Short'], $filters->getFilter('long')->getOptions());
        $this->assertSame('Any length', $this->props($filters, 'long')['placeholder']);
        $this->assertSame('Short', $filters->getFilter('long')->getChipValue('0'));
    }

    /*
    |--------------------------------------------------------------------------
    | Dates
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_filters_by_a_date(): void
    {
        $make = fn (string $operator = '=') => Filters::make([DateFilter::make('published_at')->operator($operator)]);

        $this->assertSame(['rain'], $this->filter(['published_at' => '2026-01-10'], $make()));
        $this->assertSame([], $this->filter(['published_at' => '2026-01-11'], $make()));
        $this->assertSame(['rain'], $this->filter(['published_at' => '2026-02-01'], $make('<')));
        $this->assertEqualsCanonicalizing(['rain', 'final'], $this->filter(['published_at' => '2026-02-20'], $make('<=')));
        $this->assertSame(['final'], $this->filter(['published_at' => '2026-01-10'], $make('>')));
        $this->assertEqualsCanonicalizing(['rain', 'final'], $this->filter(['published_at' => '2026-01-10'], $make('>=')));
    }

    #[Test]
    public function it_ignores_invalid_dates(): void
    {
        $filter = DateFilter::make('published_at');

        $this->assertNull($filter->normalizeValue('2026-02-30'));
        $this->assertNull($filter->normalizeValue('yesterday'));
        $this->assertNull($filter->normalizeValue('10/01/2026'));
        $this->assertNull($filter->normalizeValue(['2026-01-10']));
        $this->assertSame('2026-01-10', $filter->normalizeValue(' 2026-01-10 '));
    }

    #[Test]
    public function it_rejects_unknown_date_operators(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DateFilter::make('published_at')->operator('!=');
    }

    #[Test]
    public function it_filters_by_a_date_and_time(): void
    {
        $make = fn (string $operator = '=') => Filters::make([DateTimeFilter::make('published_at')->operator($operator)]);

        $this->assertSame(['final'], $this->filter(['published_at' => '2026-02-20 18:30'], $make()));
        $this->assertSame(['final'], $this->filter(['published_at' => '2026-02-20T18:30'], $make()));
        $this->assertSame([], $this->filter(['published_at' => '2026-02-20 18:31'], $make()));
        $this->assertSame(['rain'], $this->filter(['published_at' => '2026-02-20 18:30'], $make('<')));
        $this->assertEqualsCanonicalizing(['rain', 'final'], $this->filter(['published_at' => '2026-02-20 18:30'], $make('<=')));
        $this->assertSame([], $this->filter(['published_at' => '2026-02-20 18:30'], $make('>')));
        $this->assertSame(['final'], $this->filter(['published_at' => '2026-02-20 18:30'], $make('>=')));
        $this->assertSame('2026-02-20 18:30', DateTimeFilter::make('x')->normalizeValue('2026-02-20 18:30:00'));
        $this->assertNull(DateTimeFilter::make('x')->normalizeValue('2026-02-20'));
    }

    #[Test]
    public function it_filters_by_a_date_range(): void
    {
        $this->assertSame(['rain'], $this->filter(['published_at_from' => '2026-01-01', 'published_at_to' => '2026-01-10']));
        $this->assertSame(['final'], $this->filter(['published_at_from' => '2026-01-11']));
        $this->assertSame(['rain'], $this->filter(['published_at_to' => '2026-01-10']));
        $this->assertEqualsCanonicalizing(['rain', 'final', 'draft'], $this->filter(['published_at_from' => 'not a date']));

        $this->assertSame(
            ['published_at' => ['from' => '2026-01-01']],
            ArticleFilters::make()->fromQuery(['published_at_from' => '2026-01-01', 'published_at_to' => 'nope'])->getValues(),
        );
    }

    #[Test]
    public function it_filters_by_a_date_time_range_with_custom_parameters(): void
    {
        $filters = Filters::make([
            DateRangeFilter::make('published_at')->withTime()->parameters('date_from', 'date_to'),
        ]);

        $this->assertSame(['date_from', 'date_to'], $filters->getFilter('published_at')->getQueryKeys());
        $this->assertSame(['final'], $this->filter(['date_from' => '2026-02-20 18:30', 'date_to' => '2026-02-20 18:30'], $filters));
        $this->assertSame([], $this->filter(['date_from' => '2026-02-20 18:31'], $filters));
        $this->assertSame([], $filters->fromQuery(['date_from' => '2026-02-20'])->getValues());
    }

    /*
    |--------------------------------------------------------------------------
    | Numbers
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_filters_by_a_number_range(): void
    {
        $this->assertEqualsCanonicalizing(['rain', 'final'], $this->filter(['word_count_min' => '100', 'word_count_max' => '1500']));
        $this->assertSame(['final'], $this->filter(['word_count_min' => '1000']));
        $this->assertSame(['draft'], $this->filter(['word_count_max' => '50']));
        $this->assertSame([], ArticleFilters::make()->fromQuery(['word_count_min' => '1.5', 'word_count_max' => 'lots'])->getValues());

        $decimals = NumberRangeFilter::make('rating')->step(0.5);

        $this->assertSame(['from' => '1.5'], $decimals->normalizeValue(['from' => '1.50', 'to' => '']));
    }

    /*
    |--------------------------------------------------------------------------
    | Trashed
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_filters_deleted_rows(): void
    {
        $this->assertEqualsCanonicalizing(['rain', 'final', 'draft'], $this->filter([]));
        $this->assertEqualsCanonicalizing(['rain', 'final', 'draft', 'archived'], $this->filter(['trashed' => 'with']));
        $this->assertSame(['archived'], $this->filter(['trashed' => 'only']));
        $this->assertSame([], ArticleFilters::make()->fromQuery(['trashed' => 'all'])->getValues());
        $this->assertSame('trashed', TrashedFilter::make()->getName());
    }
}
