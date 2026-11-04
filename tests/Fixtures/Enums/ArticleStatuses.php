<?php

namespace Javaabu\Filters\Tests\Fixtures\Enums;

enum ArticleStatuses: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => __('Draft'),
            self::PUBLISHED => __('Published'),
            self::ARCHIVED => __('Archived'),
        };
    }
}
