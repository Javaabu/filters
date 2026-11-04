<?php

namespace Javaabu\Filters\Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Javaabu\Filters\Filters;
use Javaabu\Filters\MultiSelectFilter;
use Javaabu\Filters\Tests\Feature\Concerns\SeedsArticles;
use Javaabu\Filters\Tests\Fixtures\Filters\ArticleFilters;
use Javaabu\Filters\Tests\TestCase;
use Javaabu\Filters\TextFilter;
use PHPUnit\Framework\Attributes\Test;

class BladeViewsTest extends TestCase
{
    use SeedsArticles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedArticles();
    }

    protected function xpath(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function filters(array $query = []): ArticleFilters
    {
        return ArticleFilters::make()->action('/articles')->fromQuery($query);
    }

    /**
     * @return array<int, string>
     */
    protected function values(DOMXPath $xpath, string $query, string $attribute = 'value'): array
    {
        $values = [];

        foreach ($xpath->query($query) as $node) {
            $values[] = $node->getAttribute($attribute);
        }

        return $values;
    }

    #[Test]
    public function it_renders_the_filter_bar_as_a_get_form(): void
    {
        $xpath = $this->xpath($this->filters(['search' => 'ވާރޭ', 'status' => ['draft']])->toHtml());

        $form = $xpath->query('//form[@id="filter"]')->item(0);

        $this->assertSame('GET', $form->getAttribute('method'));
        $this->assertSame('/articles', $form->getAttribute('action'));
        $this->assertSame('search', $form->getAttribute('role'));

        $this->assertSame(['ވާރޭ'], $this->values($xpath, '//input[@name="search"]'));
        $this->assertSame('rtl', $xpath->query('//input[@name="search"]')->item(0)->getAttribute('dir'));
        $this->assertSame(['draft'], $this->values($xpath, '//select[@name="status[]"]/option[@selected]'));
        $this->assertCount(0, $xpath->query('//input[starts-with(@name, "sync_")]'));
        $this->assertCount(1, $xpath->query('//input[@name="published_at_from"]'));
        $this->assertCount(1, $xpath->query('//input[@name="word_count_max"]'));
        $this->assertCount(0, $xpath->query('//*[@name="author_id"]'), 'hidden filters are not rendered without a value');

        $this->assertCount(1, $xpath->query('//form//button[@type="submit"]'));
        $this->assertSame(['/articles'], $this->values($xpath, '//form//a[contains(., "Clear")]', 'href'));
    }

    #[Test]
    public function it_renders_with_the_blade_driver(): void
    {
        $filters = $this->filters();

        $this->assertSame($filters->toHtml(), (string) $filters->render());
        $this->assertSame($filters->toHtml(), Blade::render('{{ $filters }}', ['filters' => $filters]));
        $this->assertStringContainsString('data-filters="filter"', Blade::render('<x-filters::bar :filters="$filters" />', ['filters' => $filters]));
    }

    #[Test]
    public function it_keeps_the_sort_preserved_keys_and_hidden_filters_as_hidden_inputs(): void
    {
        $xpath = $this->xpath($this->filters(['author_id' => '1', 'orderby' => 'title', 'order' => 'desc', 'per_page' => '50', 'page' => '2'])->toHtml());

        $this->assertSame(['1'], $this->values($xpath, '//form//input[@type="hidden"][@name="author_id"]'));
        $this->assertSame(['title'], $this->values($xpath, '//form//input[@type="hidden"][@name="orderby"]'));
        $this->assertSame(['DESC'], $this->values($xpath, '//form//input[@type="hidden"][@name="order"]'));
        $this->assertSame(['50'], $this->values($xpath, '//form//input[@type="hidden"][@name="per_page"]'));
        $this->assertCount(0, $xpath->query('//input[@name="page"]'));
        $this->assertSame(['/articles?per_page=50'], $this->values($xpath, '//form//a[contains(., "Clear")]', 'href'));
    }

    #[Test]
    public function it_renders_array_hidden_inputs(): void
    {
        $filters = Filters::make([
            MultiSelectFilter::make('status')->options(['a' => 'A', 'b' => 'B'])->hidden(),
        ])->action('/x')->fromQuery(['status' => ['a', 'b']]);

        $this->assertSame(['a', 'b'], $this->values($this->xpath($filters->toHtml()), '//input[@type="hidden"][@name="status[]"]'));
    }

    #[Test]
    public function it_renders_the_sort_dropdown_in_the_bar(): void
    {
        $html = Blade::render('<x-filters::bar :filters="$filters" sort />', ['filters' => $this->filters(['orderby' => 'title'])]);
        $xpath = $this->xpath($html);

        $this->assertSame(['title', 'published_at', 'word_count', 'author'], $this->values($xpath, '//select[@name="orderby"]/option'));
        $this->assertSame(['title'], $this->values($xpath, '//select[@name="orderby"]/option[@selected]'));
        $this->assertSame(['ASC'], $this->values($xpath, '//select[@name="order"]/option[@selected]'));
        $this->assertCount(0, $xpath->query('//input[@type="hidden"][@name="orderby"]'));
    }

    #[Test]
    public function it_selects_the_default_sort_in_the_dropdown(): void
    {
        $xpath = $this->xpath(Blade::render('<x-filters::sort :filters="$filters" />', ['filters' => $this->filters()]));

        $this->assertSame(['published_at'], $this->values($xpath, '//select[@name="orderby"]/option[@selected]'));
        $this->assertSame(['DESC'], $this->values($xpath, '//select[@name="order"]/option[@selected]'));
        $this->assertSame('', Blade::render('<x-filters::sort :filters="$filters" />', ['filters' => Filters::make()]));
    }

    #[Test]
    public function it_passes_extra_buttons_and_export_to_the_submit_row(): void
    {
        $html = Blade::render(
            '<x-filters::bar :filters="$filters" :chips="false" export><button type="button" data-extra>Extra</button></x-filters::bar>',
            ['filters' => $this->filters(['search' => 'x'])],
        );

        $xpath = $this->xpath($html);

        $this->assertCount(1, $xpath->query('//button[@data-extra]'));
        $this->assertCount(1, $xpath->query('//button[@name="download"]'));
        $this->assertCount(0, $xpath->query('//*[@data-filter-chips]'));
    }

    #[Test]
    public function it_renders_chips_with_remove_links_and_clear_all(): void
    {
        $xpath = $this->xpath(Blade::render('<x-filters::chips :filters="$filters" />', [
            'filters' => $this->filters(['search' => 'ވާރޭ', 'is_featured' => '1', 'orderby' => 'title']),
        ]));

        $chips = $xpath->query('//*[@data-filter-chip]');

        $this->assertCount(2, $chips);
        $this->assertStringContainsString('ވާރޭ', $chips->item(0)->textContent);
        $this->assertSame(
            ['/articles?is_featured=1&orderby=title&order=ASC', '/articles?search=%DE%88%DE%A7%DE%83%DE%AD&orderby=title&order=ASC'],
            $this->values($xpath, '//a[@data-filter-chip-remove]', 'href'),
        );
        $this->assertSame(['Remove filter Search: ވާރޭ', 'Remove filter Featured: Yes'], $this->values($xpath, '//a[@data-filter-chip-remove]', 'aria-label'));
        $this->assertSame(['/articles?orderby=title&order=ASC'], $this->values($xpath, '//a[@data-filter-chips-clear]', 'href'));
    }

    #[Test]
    public function it_renders_nothing_without_applied_filters_and_no_clear_all_for_one(): void
    {
        $this->assertSame('', trim(Blade::render('<x-filters::chips :filters="$filters" />', ['filters' => $this->filters()])));

        $xpath = $this->xpath(Blade::render('<x-filters::chips :filters="$filters" />', ['filters' => $this->filters(['search' => 'x'])]));

        $this->assertCount(0, $xpath->query('//a[@data-filter-chips-clear]'));
    }

    #[Test]
    public function it_renders_sortable_headings_as_links(): void
    {
        $html = Blade::render(
            '<table><tr><x-filters::heading :filters="$filters" sort="title" /><x-filters::heading :filters="$filters" sort="published_at" label="Date" class="w-40" /><x-filters::heading :filters="$filters" sort="status" label="Status" /></tr></table>',
            ['filters' => $this->filters(['search' => 'x', 'orderby' => 'title', 'order' => 'asc'])],
        );

        $xpath = $this->xpath($html);

        $this->assertSame(['ascending', 'none'], $this->values($xpath, '//th[@data-filter-sort-heading]', 'aria-sort'));
        $this->assertSame(
            ['/articles?search=x&orderby=title&order=DESC', '/articles?search=x&orderby=published_at&order=DESC'],
            $this->values($xpath, '//th[@data-filter-sort-heading]/a', 'href'),
        );
        $this->assertStringContainsString('w-40', $xpath->query('//th[@data-filter-sort-heading="published_at"]')->item(0)->getAttribute('class'));
        $this->assertSame('Status', trim($xpath->query('//th[not(@data-filter-sort-heading)]')->item(0)->textContent));
        $this->assertSame(['Sort by Title', 'Sort by Date'], $this->values($xpath, '//th/a', 'aria-label'));
    }

    #[Test]
    public function it_marks_the_default_sort_heading(): void
    {
        $xpath = $this->xpath(Blade::render('<table><tr><x-filters::heading :filters="$filters" sort="published_at" /></tr></table>', ['filters' => $this->filters()]));

        $this->assertSame(['descending'], $this->values($xpath, '//th', 'aria-sort'));
        $this->assertSame(['/articles?orderby=published_at&order=ASC'], $this->values($xpath, '//th/a', 'href'));
    }

    #[Test]
    public function it_renders_in_dhivehi(): void
    {
        app()->setLocale('dv');

        $xpath = $this->xpath($this->filters(['search' => 'ވާރޭ', 'is_featured' => '0'])->toHtml());

        $this->assertSame('ފިލްޓަރުތައް', $xpath->query('//form')->item(0)->getAttribute('aria-label'));
        $this->assertSame(['ހޯދާ...'], $this->values($xpath, '//input[@name="search"]', 'placeholder'));
        $this->assertSame(['ބޭނުންކުރެވިފައިވާ ފިލްޓަރުތައް'], $this->values($xpath, '//*[@data-filter-chips]', 'aria-label'));
        $this->assertStringContainsString('ނޫން', $xpath->query('//*[@data-filter-chip="is_featured"]')->item(0)->textContent);
        $this->assertStringContainsString('ހުރިހާ ފިލްޓަރެއް ފޮހެލާ', $xpath->query('//a[@data-filter-chips-clear]')->item(0)->textContent);
    }

    #[Test]
    public function it_uses_the_current_url_by_default(): void
    {
        Route::get('/articles', fn () => Filters::make([TextFilter::make()->columns(['title'])])->toHtml());

        $xpath = $this->xpath($this->get('/articles?search=x&page=3')->getContent());

        $this->assertSame('http://localhost/articles', $xpath->query('//form')->item(0)->getAttribute('action'));
        $this->assertSame(['http://localhost/articles'], $this->values($xpath, '//a[@data-filter-chip-remove]', 'href'));
    }

    #[Test]
    public function it_publishes_the_views(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'filters-views'])->assertSuccessful();

        $this->assertFileExists(resource_path('views/vendor/filters/components/bar.blade.php'));

        app('files')->deleteDirectory(resource_path('views/vendor/filters'));
    }
}
