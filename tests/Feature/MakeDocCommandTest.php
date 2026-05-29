<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Feature;

use ServeraCloud\Manual\Services\FrontMatterParser;
use ServeraCloud\Manual\Tests\TestCase;

final class MakeDocCommandTest extends TestCase {
    public function test_it_creates_a_new_document_from_a_simple_name(): void {
        $this->artisan('make:doc', ['name' => 'foo'])
            ->expectsOutputToContain('Manual document created')
            ->assertSuccessful();

        $this->assertFileExists($this->docsPath . '/foo.md');

        $parsed = app(FrontMatterParser::class)->parse($this->readDoc('foo.md'), 'foo.md');

        $this->assertSame('Foo', $parsed->attributes['title']);
        $this->assertStringContainsString('# Foo', $parsed->body);
        $this->assertStringContainsString('# slug: my-slug', $this->readDoc('foo.md'));
    }

    public function test_it_creates_a_nested_document_and_accepts_the_md_extension(): void {
        $this->artisan('make:doc', ['name' => 'guides/install.md'])
            ->assertSuccessful();

        $this->assertFileExists($this->docsPath . '/guides/install.md');
    }

    public function test_it_derives_the_section_title_for_nested_index_documents(): void {
        $this->artisan('make:doc', ['name' => 'guides/index'])
            ->assertSuccessful();

        $parsed = app(FrontMatterParser::class)->parse($this->readDoc('guides/index.md'), 'guides/index.md');

        $this->assertSame('Guides', $parsed->attributes['title']);
        $this->assertStringContainsString('# Guides', $parsed->body);
    }

    public function test_it_serializes_supported_front_matter_options_safely(): void {
        $this->artisan('make:doc', [
            'name' => 'guides/safe',
            '--title' => 'Safe "Guide": Intro',
            '--slug' => 'safe-guide',
            '--route' => 'guides/custom-safe',
            '--order' => '7',
            '--description' => 'Quotes: "yes", colon: ok',
            '--route-name' => 'guides.safe',
            '--hidden' => true,
        ])->assertSuccessful();

        $parsed = app(FrontMatterParser::class)->parse($this->readDoc('guides/safe.md'), 'guides/safe.md');

        $this->assertSame([
            'title' => 'Safe "Guide": Intro',
            'slug' => 'safe-guide',
            'route' => 'guides/custom-safe',
            'order' => 7,
            'description' => 'Quotes: "yes", colon: ok',
            'route_name' => 'guides.safe',
            'hidden' => true,
        ], $parsed->attributes);
        $this->assertStringContainsString('# Safe "Guide": Intro', $parsed->body);
    }

    public function test_it_refuses_to_overwrite_an_existing_document_without_force(): void {
        $this->writeDoc('guides/existing.md', "# Existing\n");

        $this->artisan('make:doc', ['name' => 'guides/existing'])
            ->expectsOutputToContain('already exists')
            ->assertFailed();

        $this->assertSame("# Existing\n", $this->readDoc('guides/existing.md'));
    }

    public function test_it_overwrites_an_existing_document_with_force(): void {
        $this->writeDoc('guides/existing.md', "# Existing\n");

        $this->artisan('make:doc', [
            'name' => 'guides/existing',
            '--title' => 'Updated',
            '--force' => true,
        ])->assertSuccessful();

        $parsed = app(FrontMatterParser::class)->parse($this->readDoc('guides/existing.md'), 'guides/existing.md');

        $this->assertSame('Updated', $parsed->attributes['title']);
    }

    public function test_it_rejects_path_traversal_inputs(): void {
        $this->artisan('make:doc', ['name' => '../escape'])
            ->expectsOutputToContain('invalid path segment')
            ->assertFailed();

        $this->assertFileDoesNotExist(dirname($this->docsPath) . '/escape.md');
    }

    public function test_it_rejects_absolute_paths(): void {
        $this->artisan('make:doc', ['name' => '/tmp/escape'])
            ->expectsOutputToContain('must be relative to manual.source_path')
            ->assertFailed();

        $this->artisan('make:doc', ['name' => 'C:\\temp\\escape'])
            ->expectsOutputToContain('must be relative to manual.source_path')
            ->assertFailed();
    }

    public function test_it_rejects_invalid_order_values(): void {
        $this->artisan('make:doc', [
            'name' => 'guides/invalid-order',
            '--order' => 'abc',
        ])->expectsOutputToContain('must be an integer')
            ->assertFailed();
    }

    public function test_it_rejects_symlink_escape_when_supported_by_the_filesystem(): void {
        $outside = tempnam(sys_get_temp_dir(), 'manual-outside-');

        if ($outside === false) {
            $this->markTestSkipped('Could not allocate a temporary path for the symlink test.');
        }

        @unlink($outside);
        mkdir($outside);

        if (! @symlink($outside, $this->docsPath . '/shared')) {
            $this->files->deleteDirectory($outside);
            $this->markTestSkipped('The filesystem does not allow symlink creation in this environment.');
        }

        try {
            $this->artisan('make:doc', ['name' => 'shared/escape'])
                ->expectsOutputToContain('resolves outside of manual.source_path')
                ->assertFailed();

            $this->assertFileDoesNotExist($outside . '/escape.md');
        } finally {
            @unlink($this->docsPath . '/shared');
            $this->files->deleteDirectory($outside);
        }
    }
}
