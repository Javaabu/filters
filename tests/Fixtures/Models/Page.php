<?php

namespace Javaabu\Filters\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A model with a search scope, which TextFilter uses when it has no columns.
 */
class Page extends Model
{
    protected $guarded = [];

    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where('title', 'like', "%{$search}%")->orWhere('slug', $search);
    }
}
