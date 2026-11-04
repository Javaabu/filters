<?php

namespace Javaabu\Filters\Tests\Feature;

use Javaabu\Filters\Tests\Feature\Concerns\SeedsArticles;
use Javaabu\Filters\Tests\Fixtures\Filters\ArticleFilters;
use Javaabu\Filters\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Keeps the JSON the React tests render in step with $filters->toArray().
 * Run with FILTERS_UPDATE_FIXTURES=1 to rewrite it.
 */
class JsFixtureTest extends TestCase
{
    use SeedsArticles;

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    protected function fixtures(): array
    {
        return [
            'articles.json' => [[
                'search' => 'ވާރޭ',
                'status' => ['draft', 'published'],
                'author_id' => '1',
                'published_at_from' => '2026-01-01',
                'orderby' => 'title',
                'order' => 'desc',
                'per_page' => '50',
            ], 'en'],
            'articles-empty.json' => [[], 'en'],
            'articles-dv.json' => [['is_featured' => '1', 'trashed' => 'only'], 'dv'],
        ];
    }

    #[Test]
    public function it_matches_the_json_the_react_tests_render(): void
    {
        $this->seedArticles();

        foreach ($this->fixtures() as $file => [$query, $locale]) {
            app()->setLocale($locale);

            $json = ArticleFilters::make()->action('/articles')->fromQuery($query)->toArray();

            $encoded = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            $path = __DIR__ . "/../js/fixtures/{$file}";

            if (getenv('FILTERS_UPDATE_FIXTURES')) {
                file_put_contents($path, $encoded);
            }

            $this->assertJsonStringEqualsJsonFile($path, $encoded, "{$file} is out of date: run FILTERS_UPDATE_FIXTURES=1 vendor/bin/phpunit --filter JsFixtureTest.");
        }
    }
}
