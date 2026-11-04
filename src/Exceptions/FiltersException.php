<?php

namespace Javaabu\Filters\Exceptions;

use InvalidArgumentException;
use Javaabu\Filters\Filter;

class FiltersException extends InvalidArgumentException
{
    public static function missingName(string $class): self
    {
        return new self(sprintf('[%s] needs a name: %s::make(\'name\').', $class, class_basename($class)));
    }

    public static function missingQuery(string $filter): self
    {
        return new self(sprintf(
            'The [%s] filter has several fields, so it needs ->query(fn (Builder $query, array $value) => …) to apply them.',
            $filter,
        ));
    }

    public static function missingModel(string $filter): self
    {
        return new self(sprintf(
            'The [%s] filter uses a relationship, so the filters need a model: Filters::make([...])->model(Article::class), or protected ?string $model in a filters class.',
            $filter,
        ));
    }

    public static function duplicateFilter(string $filter): self
    {
        return new self(sprintf('There are two filters named [%s]. Filter names must be unique.', $filter));
    }

    public static function invalidFilter(mixed $filter): self
    {
        return new self(sprintf('Filters must be instances of %s, [%s] given.', Filter::class, get_debug_type($filter)));
    }
}
