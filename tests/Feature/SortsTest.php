<?php

namespace Javaabu\Filters\Tests\Feature;

use Javaabu\Filters\Filters;
use Javaabu\Filters\Sort;
use Javaabu\Filters\Tests\Feature\Concerns\SeedsArticles;
use Javaabu\Filters\Tests\Fixtures\Filters\ArticleFilters;
use Javaabu\Filters\Tests\Fixtures\Models\Article;
use Javaabu\Filters\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SortsTest extends TestCase
{
    use SeedsArticles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedArticles();
    }

    #[Test]
    public function it_applies_the_default_sort_without_one_in_the_url(): void
    {
        $filters = ArticleFilters::make()->fromQuery([]);

        $this->assertSame('published_at', $filters->getSortName());
        $this->assertSame('desc', $filters->getDirection());
        $this->assertTrue($filters->isSortedBy('published_at', 'DESC'));
        $this->assertSame(['final', 'rain', 'draft'], $this->slugs($filters->apply(Article::query())));
    }

    #[Test]
    public function it_applies_the_sort_in_the_url(): void
    {
        $filters = ArticleFilters::make()->fromQuery(['orderby' => 'title', 'order' => 'ASC']);

        $this->assertSame(['draft', 'final', 'rain'], $this->slugs($filters->apply(Article::query())));

        $filters->fromQuery(['orderby' => 'word_count', 'order' => 'desc']);

        $this->assertSame(['final', 'rain', 'draft'], $this->slugs($filters->apply(Article::query())));
    }

    #[Test]
    public function it_uses_the_sorts_default_direction_when_the_url_has_none(): void
    {
        $filters = ArticleFilters::make()->fromQuery(['orderby' => 'published_at']);

        $this->assertSame('desc', $filters->getDirection());

        $filters->fromQuery(['orderby' => 'title']);

        $this->assertSame('asc', $filters->getDirection());
    }

    #[Test]
    public function it_ignores_sorts_that_arent_registered(): void
    {
        $filters = ArticleFilters::make()->fromQuery(['orderby' => 'password', 'order' => 'sideways']);

        $this->assertNull($filters->getRequestedSort());
        $this->assertSame('published_at', $filters->getSortName());
        $this->assertSame('desc', $filters->getDirection());
        $this->assertSame([], $filters->getSortParameters());

        $sql = $filters->apply(Article::query())->toSql();

        $this->assertStringNotContainsString('password', $sql);
        $this->assertStringContainsString('order by "articles"."published_at" desc', $sql);
    }

    #[Test]
    public function it_ignores_sorts_that_arent_strings(): void
    {
        $filters = ArticleFilters::make()->fromQuery(['orderby' => ['title'], 'order' => ['asc']]);

        $this->assertSame('published_at', $filters->getSortName());
        $this->assertSame('desc', $filters->getDirection());
    }

    #[Test]
    public function it_sorts_with_a_closure(): void
    {
        $filters = ArticleFilters::make()->fromQuery(['orderby' => 'author', 'order' => 'asc']);

        $slugs = $this->slugs($filters->apply(Article::query()));

        $this->assertEqualsCanonicalizing(['final', 'draft'], array_slice($slugs, 0, 2));
        $this->assertSame('rain', $slugs[2]);
    }

    #[Test]
    public function it_sorts_by_a_default_that_isnt_one_of_the_sorts(): void
    {
        $filters = Filters::make([], ['title'])->defaultSort('word_count', 'DESC')->fromQuery([]);

        $this->assertSame(['final', 'rain', 'draft'], $this->slugs($filters->apply(Article::query())));
        $this->assertFalse($filters->hasSort('word_count'));
    }

    #[Test]
    public function it_doesnt_sort_without_sorts_or_a_default(): void
    {
        $filters = Filters::make()->fromQuery(['orderby' => 'title']);

        $this->assertNull($filters->getSortName());
        $this->assertStringNotContainsString('order by', $filters->applySort(Article::query())->toSql());
    }

    #[Test]
    public function it_uses_the_configured_parameters(): void
    {
        config(['filters.query.sort' => 'sort', 'filters.query.direction' => 'dir']);

        $filters = ArticleFilters::make()->fromQuery(['sort' => 'title', 'dir' => 'desc']);

        $this->assertSame('title', $filters->getSortName());
        $this->assertSame(['sort' => 'title', 'dir' => 'DESC'], $filters->getSortParameters());
    }

    #[Test]
    public function it_builds_sort_urls(): void
    {
        $filters = ArticleFilters::make()->action('/articles')->fromQuery(['status' => ['draft'], 'per_page' => '50', 'page' => '3']);

        $this->assertSame('/articles?status%5B0%5D=draft&orderby=title&order=ASC&per_page=50', $filters->sortUrl('title'));
        $this->assertSame('/articles?status%5B0%5D=draft&orderby=published_at&order=ASC&per_page=50', $filters->sortUrl('published_at'));

        $filters->fromQuery(['orderby' => 'title', 'order' => 'asc']);

        $this->assertSame('/articles?orderby=title&order=DESC', $filters->sortUrl('title'));
        $this->assertSame('/articles?orderby=title&order=ASC', $filters->sortUrl('title', 'asc'));
        $this->assertSame('/articles?orderby=word_count&order=ASC', $filters->sortUrl('word_count'));
    }

    #[Test]
    public function it_labels_sorts(): void
    {
        $this->assertSame('Published At', Sort::make('published_at')->getLabel());
        $this->assertSame('Newest', Sort::make('published_at')->label('Newest')->getLabel());
        $this->assertSame('created_at', Sort::make('newest')->column('created_at')->getColumn());
        $this->assertSame('asc', Sort::make('x')->defaultDirection('sideways')->getDefaultDirection());
        $this->assertSame(['title' => 'Title', 'published_at' => 'Published At', 'word_count' => 'Word Count', 'author' => 'Author'], ArticleFilters::make()->getSortOptions());
    }
}
