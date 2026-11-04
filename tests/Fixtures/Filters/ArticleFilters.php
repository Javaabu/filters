<?php

namespace Javaabu\Filters\Tests\Fixtures\Filters;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Filters\BooleanFilter;
use Javaabu\Filters\DateRangeFilter;
use Javaabu\Filters\Filters;
use Javaabu\Filters\NumberRangeFilter;
use Javaabu\Filters\SelectFilter;
use Javaabu\Filters\Sort;
use Javaabu\Filters\TernaryFilter;
use Javaabu\Filters\Tests\Fixtures\Enums\ArticleStatuses;
use Javaabu\Filters\Tests\Fixtures\Models\Article;
use Javaabu\Filters\Tests\Fixtures\Models\Author;
use Javaabu\Filters\TextFilter;
use Javaabu\Filters\TrashedFilter;

class ArticleFilters extends Filters
{
    protected ?string $model = Article::class;

    protected ?string $default_sort = 'published_at';

    protected string $default_direction = 'desc';

    protected function filters(): array
    {
        return [
            TextFilter::make('search')->columns(['title', 'author.name'])->thaana(),
            SelectFilter::make('status')->options(ArticleStatuses::class)->multiple(),
            SelectFilter::make('category_id')->relationship('category', 'name'),
            SelectFilter::make('tags')->relationship('tags', 'name')->multiple(),
            TernaryFilter::make('is_featured')->label('Featured'),
            TernaryFilter::make('reviewed')->column('reviewed_at')->nullable(),
            BooleanFilter::make('long')->label('Long reads')->query(fn (Builder $query) => $query->where('word_count', '>=', 1000)),
            DateRangeFilter::make('published_at'),
            NumberRangeFilter::make('word_count')->integer(),
            TrashedFilter::make(),
            SelectFilter::make('author_id')->relationship('author', 'name')->hidden(),
        ];
    }

    protected function sorts(): array
    {
        return [
            Sort::make('title'),
            Sort::make('published_at')->defaultDirection('desc'),
            'word_count',
            Sort::make('author')->using(fn (Builder $query, string $direction) => $query->orderBy(
                Author::select('name')->whereColumn('authors.id', 'articles.author_id'),
                $direction,
            )),
        ];
    }
}
