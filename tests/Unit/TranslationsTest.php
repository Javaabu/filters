<?php

namespace Javaabu\Filters\Tests\Unit;

use Javaabu\Filters\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslationsTest extends TestCase
{
    #[Test]
    public function it_has_every_string_in_english_and_dhivehi(): void
    {
        $root = dirname(__DIR__, 2) . '/lang';

        $en = require "{$root}/en/strings.php";
        $dv = require "{$root}/dv/strings.php";

        $this->assertSame(array_keys($en), array_keys($dv));

        foreach ($dv as $key => $value) {
            preg_match_all('/:\w+/', $en[$key], $en_placeholders);
            preg_match_all('/:\w+/', $value, $dv_placeholders);

            $this->assertEqualsCanonicalizing($en_placeholders[0], $dv_placeholders[0], "dv.{$key} has different placeholders.");
        }
    }

    #[Test]
    public function it_writes_dhivehi_in_thaana(): void
    {
        $dv = require dirname(__DIR__, 2) . '/lang/dv/strings.php';

        $untranslated = ['chip', 'list_separator', 'range'];

        foreach (array_diff_key($dv, array_flip($untranslated)) as $key => $value) {
            $this->assertMatchesRegularExpression('/\p{Thaana}/u', $value, "dv.{$key} isn't in Thaana.");
        }
    }

    #[Test]
    public function it_uses_logical_utilities_in_the_views(): void
    {
        $files = [...glob(dirname(__DIR__, 2) . '/resources/views/components/*.blade.php'), ...glob(dirname(__DIR__, 2) . '/resources/js/*.tsx')];

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '/\b(ml|mr|pl|pr|left|right|text-left|text-right|border-l|border-r|rounded-l|rounded-r)-/',
                file_get_contents($file),
                basename($file) . ' uses a physical direction utility.',
            );
        }
    }
}
