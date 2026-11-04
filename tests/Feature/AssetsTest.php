<?php

namespace Javaabu\Filters\Tests\Feature;

use Javaabu\Filters\Tests\TestCase;
use Javaabu\Manifest\Facades\Manifest;
use PHPUnit\Framework\Attributes\Test;

class AssetsTest extends TestCase
{
    #[Test]
    public function it_ships_the_react_components_through_javaabu_manifest(): void
    {
        $this->assertTrue(Manifest::hasPackage('filters'));

        $package = Manifest::package('filters');

        $this->assertSame('@javaabu/filters', $package->importAlias());
        $this->assertSame('js', $package->jsPath());
        $this->assertFileExists(dirname(__DIR__, 2) . '/resources/js/index.ts');
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies());
        $this->assertArrayHasKey('react', $package->npmPeerDependencies());
    }
}
