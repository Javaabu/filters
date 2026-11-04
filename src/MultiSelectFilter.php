<?php

namespace Javaabu\Filters;

/**
 * A SelectFilter that picks several values (status[]=draft&status[]=published).
 */
class MultiSelectFilter extends SelectFilter
{
    protected function setUp(): void
    {
        $this->multiple();
    }
}
