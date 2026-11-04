<?php

namespace Javaabu\Filters\Tests\Feature\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Javaabu\Filters\Tests\Fixtures\Models\Article;
use Javaabu\Filters\Tests\Fixtures\Models\Author;
use Javaabu\Filters\Tests\Fixtures\Models\Category;
use Javaabu\Filters\Tests\Fixtures\Models\Tag;

trait SeedsArticles
{
    protected function seedArticles(): void
    {
        $this->setUpDatabase();

        $news = Category::create(['name' => 'ޚަބަރު']);
        $sports = Category::create(['name' => 'ކުޅިވަރު']);

        $aisha = Author::create(['name' => 'ޢާއިޝާ']);
        $ahmed = Author::create(['name' => 'Ahmed']);

        $local = Tag::create(['name' => 'ރާއްޖެ']);
        $world = Tag::create(['name' => 'World']);

        Article::create([
            'title' => 'މާލޭގައި ވާރޭ', 'slug' => 'rain', 'status' => 'published', 'category_id' => $news->id,
            'author_id' => $aisha->id, 'is_featured' => true, 'word_count' => 400,
            'published_at' => '2026-01-10 08:00:00', 'reviewed_at' => '2026-01-11 08:00:00',
        ])->tags()->attach($local);

        Article::create([
            'title' => 'Football final', 'slug' => 'final', 'status' => 'published', 'category_id' => $sports->id,
            'author_id' => $ahmed->id, 'word_count' => 1500, 'published_at' => '2026-02-20 18:30:00',
        ])->tags()->attach([$local->id, $world->id]);

        Article::create([
            'title' => '100% draft_one', 'slug' => 'draft', 'status' => 'draft', 'category_id' => $news->id,
            'author_id' => $ahmed->id, 'word_count' => 50,
        ]);

        Article::create([
            'title' => 'Archived story', 'slug' => 'archived', 'status' => 'archived', 'category_id' => $sports->id,
            'author_id' => $aisha->id, 'word_count' => 2000, 'published_at' => '2025-12-31 23:59:00',
        ])->delete();
    }

    /**
     * @return array<int, string>
     */
    protected function slugs(Builder $query): array
    {
        return $query->pluck('slug')->all();
    }
}
