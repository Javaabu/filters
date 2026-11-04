<?php

namespace Javaabu\Filters\Tests\Feature;

use Javaabu\Filters\Tests\Feature\Concerns\SeedsArticles;
use Javaabu\Filters\Tests\Fixtures\Filters\ArticleFilters;
use Javaabu\Filters\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class InertiaTest extends TestCase
{
    use SeedsArticles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedArticles();
    }

    #[Test]
    public function it_serializes_the_filters_for_the_react_components(): void
    {
        $json = ArticleFilters::make()->action('/articles')->fromQuery(['search' => 'ވާރޭ', 'orderby' => 'title'])->toArray();

        $this->assertSame('filter', $json['id']);
        $this->assertSame(1, $json['form']['stencil']);
        $this->assertSame('GET', $json['form']['method']);
        $this->assertTrue($json['form']['embedded']);
        $this->assertSame('ވާރޭ', $json['form']['state']['search']);
        $this->assertSame(['orderby' => 'title', 'order' => 'ASC'], $json['hidden']);
        $this->assertSame(1, $json['active']);
        $this->assertSame('Search: ވާރޭ', $json['chips'][0]['text']);
        $this->assertSame('title', $json['sort']['current']);
        $this->assertSame('/articles?search=%DE%88%DE%A7%DE%83%DE%AD&orderby=title&order=DESC', $json['sort']['options'][0]['url']);
        $this->assertSame('Filters', $json['strings']['filters']);
        $this->assertSame($json, ArticleFilters::make()->action('/articles')->fromQuery(['search' => 'ވާރޭ', 'orderby' => 'title'])->jsonSerialize());
    }

    #[Test]
    public function it_renders_with_the_configured_driver(): void
    {
        $filters = ArticleFilters::make()->action('/articles');

        $this->assertIsArray($filters->render('inertia'));
        $this->assertIsArray($filters->render('json'));

        config(['filters.views' => 'inertia']);

        $this->assertIsArray($filters->render());

        config(['filters.views' => null, 'stencil.views' => 'inertia']);

        $this->assertIsArray($filters->render());

        config(['stencil.views' => 'blade']);

        $this->assertStringContainsString('<form', (string) $filters->render());
    }

    #[Test]
    public function it_sends_strings_in_the_app_locale(): void
    {
        app()->setLocale('dv');

        $strings = ArticleFilters::make()->toArray()['strings'];

        $this->assertSame('ފިލްޓަރު ކުރޭ', $strings['filter']);
        $this->assertSame('ތަރުތީބުކުރާނީ', $strings['sortBy']);
        $this->assertSame(':filter ފިލްޓަރު ފޮހެލާ', $strings['remove']);
    }
}
