<?php

namespace Javaabu\Filters\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Javaabu\Filters\Tests\TestCase;
use Javaabu\Manifest\Enums\NpmScopes;
use Javaabu\Manifest\Facades\Manifest;
use PHPUnit\Framework\Attributes\Test;

class AssetsTest extends TestCase
{
    protected function tearDown(): void
    {
        @unlink(base_path('package.json'));

        parent::tearDown();
    }

    /**
     * @param  array<string, string>  $dependencies
     * @return array<int, array{name: string, needed_by: array<int, string>, scopes: array<int, string>}>
     */
    protected function check(array $dependencies): array
    {
        file_put_contents(base_path('package.json'), json_encode(['private' => true, 'devDependencies' => $dependencies]));

        Artisan::call('manifest:check', ['--json' => true]);

        return json_decode(Artisan::output(), true);
    }

    /**
     * @param  array<int, array{name: string, needed_by: array<int, string>}>  $rows
     * @return array<int, string>
     */
    protected function neededByFilters(array $rows): array
    {
        $rows = array_filter($rows, fn (array $row) => in_array('filters', $row['needed_by'], true));

        return array_values(array_map(fn (array $row) => $row['name'], $rows));
    }

    #[Test]
    public function it_ships_the_react_components_through_javaabu_manifest(): void
    {
        $this->assertTrue(Manifest::hasPackage('filters'));

        $package = Manifest::package('filters');

        $this->assertSame('@javaabu/filters', $package->importAlias());
        $this->assertSame('js', $package->jsPath());
        $this->assertFileExists(dirname(__DIR__, 2) . '/resources/js/index.ts');
    }

    #[Test]
    public function it_declares_its_npm_dependencies_for_pages_only(): void
    {
        $package = Manifest::package('filters');

        $this->assertSame([], $package->npmDependencies(NpmScopes::ASSETS));
        $this->assertSame([], $package->npmPeerDependencies(NpmScopes::ASSETS));
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies(NpmScopes::PAGES));
        $this->assertSame(['react' => '^19.0', 'react-dom' => '^19.0'], $package->npmPeerDependencies(NpmScopes::PAGES));
    }

    #[Test]
    public function it_asks_blade_only_apps_for_nothing(): void
    {
        $this->assertSame([], $this->neededByFilters($this->check(['vite' => '^7.0', 'laravel-vite-plugin' => '^2.0'])));
    }

    #[Test]
    public function it_asks_inertia_apps_for_the_react_dependencies(): void
    {
        $rows = $this->check(['vite' => '^7.0', 'laravel-vite-plugin' => '^2.0', '@inertiajs/react' => '^2.0']);

        $needed = $this->neededByFilters($rows);

        foreach (['@javaabu/forms', 'react', 'react-dom'] as $dependency) {
            $this->assertContains($dependency, $needed);
        }

        foreach ($rows as $row) {
            if (in_array('filters', $row['needed_by'], true)) {
                $this->assertContains('pages', $row['scopes']);
            }
        }
    }
}
