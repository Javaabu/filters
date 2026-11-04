<?php

namespace Javaabu\Filters\Tests\Feature;

use Javaabu\Filters\Chip;
use Javaabu\Filters\DateFilter;
use Javaabu\Filters\DateRangeFilter;
use Javaabu\Filters\Filters;
use Javaabu\Filters\NumberRangeFilter;
use Javaabu\Filters\SelectFilter;
use Javaabu\Filters\Tests\Feature\Concerns\SeedsArticles;
use Javaabu\Filters\Tests\Fixtures\Enums\ArticleStatuses;
use Javaabu\Filters\Tests\Fixtures\Filters\ArticleFilters;
use Javaabu\Filters\Tests\Fixtures\Models\Author;
use Javaabu\Filters\Tests\Fixtures\Models\Category;
use Javaabu\Filters\Tests\TestCase;
use Javaabu\Filters\TextFilter;
use PHPUnit\Framework\Attributes\Test;

class ChipsTest extends TestCase
{
    use SeedsArticles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedArticles();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, Chip>
     */
    protected function chips(array $query, ?Filters $filters = null): array
    {
        $filters ??= ArticleFilters::make();

        $chips = $filters->action('/articles')->fromQuery($query)->chips();

        return array_combine(array_map(fn (Chip $chip) => $chip->filter, $chips), $chips);
    }

    #[Test]
    public function it_shows_a_chip_for_each_applied_filter(): void
    {
        $news = Category::firstWhere('name', 'ޚަބަރު');

        $chips = $this->chips(['search' => 'ވާރޭ', 'status' => ['draft', 'published'], 'category_id' => (string) $news->id, 'is_featured' => '1']);

        $this->assertSame(['search', 'status', 'category_id', 'is_featured'], array_keys($chips));
        $this->assertSame('Search: ވާރޭ', $chips['search']->text());
        $this->assertSame('Draft, Published', $chips['status']->value);
        $this->assertSame('Category: ޚަބަރު', $chips['category_id']->text());
        $this->assertSame('Featured: Yes', $chips['is_featured']->text());
    }

    #[Test]
    public function it_shows_the_label_alone_for_checkboxes(): void
    {
        $chip = $this->chips(['long' => '1'])['long'];

        $this->assertNull($chip->value);
        $this->assertSame('Long reads', $chip->text());
    }

    #[Test]
    public function it_shows_chips_for_hidden_filters(): void
    {
        $aisha = Author::firstWhere('name', 'ޢާއިޝާ');

        $this->assertSame('Author: ޢާއިޝާ', $this->chips(['author_id' => (string) $aisha->id])['author_id']->text());
    }

    #[Test]
    public function it_formats_dates_and_ranges(): void
    {
        $chips = $this->chips(['published_at_from' => '2026-01-01', 'published_at_to' => '2026-03-31', 'word_count_min' => '100']);

        $this->assertSame('Published At: 1 Jan 2026 – 31 Mar 2026', $chips['published_at']->text());
        $this->assertSame('At least 100', $chips['word_count']->value);

        $this->assertSame('Until 31 Mar 2026', $this->chips(['published_at_to' => '2026-03-31'])['published_at']->value);
        $this->assertSame('From 1 Jan 2026', $this->chips(['published_at_from' => '2026-01-01'])['published_at']->value);
        $this->assertSame('At most 5', $this->chips(['word_count_max' => '5'])['word_count']->value);

        $filters = Filters::make([
            DateFilter::make('published_at')->operator('>='),
            DateRangeFilter::make('reviewed_at')->withTime(),
            DateFilter::make('created_at'),
        ]);

        $chips = $this->chips(['published_at' => '2026-02-01', 'reviewed_at_from' => '2026-02-01 09:30', 'created_at' => '2026-02-03'], $filters);

        $this->assertSame('From 1 Feb 2026', $chips['published_at']->value);
        $this->assertSame('From 1 Feb 2026 09:30', $chips['reviewed_at']->value);
        $this->assertSame('3 Feb 2026', $chips['created_at']->value);
    }

    #[Test]
    public function it_formats_dates_with_the_configured_format(): void
    {
        config(['filters.date_format' => 'd/m/Y']);

        $this->assertSame('From 01/01/2026', $this->chips(['published_at_from' => '2026-01-01'])['published_at']->value);
    }

    #[Test]
    public function it_formats_chips_in_dhivehi(): void
    {
        app()->setLocale('dv');

        $chips = $this->chips(['status' => ['draft', 'published'], 'published_at_from' => '2026-01-01', 'is_featured' => '0', 'trashed' => 'only']);

        $this->assertSame('Draft، Published', $chips['status']->value);
        $this->assertSame('1 ޖަނަވަރީ 2026 އިން', $chips['published_at']->value);
        $this->assertSame('ނޫން', $chips['is_featured']->value);
        $this->assertSame('ފޮހެލެވިފައިވާ: ހަމައެކަނި ފޮހެލެވިފައިވާ ތައް', $chips['trashed']->text());
    }

    #[Test]
    public function it_formats_chips_with_a_closure(): void
    {
        $filters = Filters::make([
            NumberRangeFilter::make('word_count')->formatChipUsing(fn (array $value) => "{$value['from']}+ words"),
            TextFilter::make()->formatChipUsing(fn () => null),
        ]);

        $chips = $this->chips(['word_count_min' => '100', 'search' => 'x'], $filters);

        $this->assertSame('100+ words', $chips['word_count']->value);
        $this->assertSame('Search', $chips['search']->text());
    }

    #[Test]
    public function it_links_each_chip_to_the_url_without_it(): void
    {
        $chips = $this->chips([
            'search' => 'ވާރޭ',
            'status' => ['draft'],
            'published_at_from' => '2026-01-01',
            'published_at_to' => '2026-03-31',
            'orderby' => 'title',
            'order' => 'desc',
            'per_page' => '50',
            'page' => '4',
            'unknown' => 'x',
        ]);

        $this->assertSame(
            '/articles?status%5B0%5D=draft&published_at_from=2026-01-01&published_at_to=2026-03-31&orderby=title&order=DESC&per_page=50',
            $chips['search']->url,
        );

        $this->assertSame(
            '/articles?search=%DE%88%DE%A7%DE%83%DE%AD&status%5B0%5D=draft&orderby=title&order=DESC&per_page=50',
            $chips['published_at']->url,
        );
    }

    #[Test]
    public function it_keeps_a_cleared_filter_with_a_default_empty(): void
    {
        $filters = Filters::make([
            SelectFilter::make('status')->options(ArticleStatuses::class)->default('published'),
            TextFilter::make(),
        ])->action('/articles');

        $chips = $this->chips(['search' => 'x'], $filters);

        $this->assertSame('/articles?search=x&status=', $chips['status']->url);
        $this->assertSame('/articles?status=published', $chips['search']->url);
        $this->assertSame([], $filters->fromQuery(['status' => ''])->getValues());
    }

    #[Test]
    public function it_builds_clear_and_reset_urls(): void
    {
        $filters = ArticleFilters::make()->action('/articles')->fromQuery([
            'search' => 'x',
            'orderby' => 'title',
            'order' => 'asc',
            'per_page' => '50',
        ]);

        $this->assertSame('/articles?orderby=title&order=ASC&per_page=50', $filters->clearUrl());
        $this->assertSame('/articles?per_page=50', $filters->resetUrl());
        $this->assertSame('/articles?search=x&orderby=title&order=ASC&per_page=50', $filters->urlWithout('not-a-filter'));
    }

    #[Test]
    public function it_preserves_the_configured_parameters(): void
    {
        config(['filters.query.preserve' => ['per_page', 'tab']]);

        $filters = ArticleFilters::make()->action('/articles')->fromQuery(['tab' => 'mine', 'per_page' => '10', 'search' => 'x']);

        $this->assertSame('/articles?tab=mine&per_page=10', $filters->clearUrl());
        $this->assertSame('/articles', $filters->preserve([])->clearUrl());
    }

    #[Test]
    public function it_returns_the_query_parameters_to_carry_along(): void
    {
        $filters = ArticleFilters::make()->fromQuery([
            'search' => ' ވާރޭ ',
            'status' => ['draft', 'nope'],
            'orderby' => 'title',
            'page' => '2',
        ]);

        $this->assertSame([
            'search' => 'ވާރޭ',
            'status' => ['draft'],
            'orderby' => 'title',
            'order' => 'ASC',
        ], $filters->getQueryParameters());

        $rebuilt = ArticleFilters::make()->fromQuery($filters->getQueryParameters());

        $this->assertSame($filters->getValues(), $rebuilt->getValues());
    }

    #[Test]
    public function it_serializes_chips(): void
    {
        $chip = new Chip('status', 'Status', 'Draft', '/articles');

        $this->assertSame(['filter' => 'status', 'label' => 'Status', 'value' => 'Draft', 'text' => 'Status: Draft', 'url' => '/articles'], $chip->jsonSerialize());
        $this->assertSame('Status', (new Chip('status', 'Status', '', '/'))->text());
    }
}
