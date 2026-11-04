<?php

namespace Javaabu\Filters\Tests\Unit;

use Javaabu\Filters\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_merges_the_default_config(): void
    {
        $this->assertSame('orderby', config('filters.query.sort'));
        $this->assertSame('order', config('filters.query.direction'));
        $this->assertSame(['per_page'], config('filters.query.preserve'));
    }

    #[Test]
    public function it_loads_the_translations(): void
    {
        $this->assertSame('Filter', __('filters::strings.filter'));

        app()->setLocale('dv');

        $this->assertSame('ފިލްޓަރު ކުރޭ', __('filters::strings.filter'));
    }
}
