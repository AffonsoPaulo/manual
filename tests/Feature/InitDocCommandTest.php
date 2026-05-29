<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Feature;

use ServeraCloud\Manual\Tests\TestCase;

final class InitDocCommandTest extends TestCase {
    public function test_it_creates_the_default_manual_scaffold_in_an_absolute_source_path(): void {
        $this->artisan('manual:init')
            ->expectsOutputToContain('Manual scaffold complete')
            ->assertSuccessful();

        $this->assertFileExists($this->docsPath . '/index.md');
        $this->assertFileExists($this->docsPath . '/getting-started/installation.md');
        $this->assertFileExists($this->docsPath . '/guides/linking.md');
        $this->assertFileExists($this->docsPath . '/advanced/customization.md');
    }

    public function test_it_creates_the_default_manual_scaffold_in_a_relative_source_path(): void {
        $relativePath = 'docs/manual-' . uniqid();
        $this->useManualSourcePath($relativePath);

        try {
            $this->artisan('manual:init')
                ->expectsOutputToContain('Manual scaffold complete')
                ->assertSuccessful();

            $this->assertFileExists($this->docsPath . '/index.md');
            $this->assertFileExists($this->docsPath . '/guides/front-matter.md');
        } finally {
            $this->files->deleteDirectory($this->docsPath);
        }
    }

    public function test_it_skips_existing_files_and_only_overwrites_known_targets_with_force(): void {
        $this->writeDoc('index.md', "# Custom Home\n");
        $this->writeDoc('custom.md', "# Keep Me\n");

        $this->artisan('manual:init')
            ->expectsOutputToContain('skipped')
            ->assertSuccessful();

        $this->assertStringContainsString('Custom Home', $this->readDoc('index.md'));
        $this->assertSame("# Keep Me\n", $this->readDoc('custom.md'));

        $this->artisan('manual:init', ['--force' => true])
            ->expectsOutputToContain('overwritten')
            ->assertSuccessful();

        $this->assertStringContainsString('Servera Manual', $this->readDoc('index.md'));
        $this->assertSame("# Keep Me\n", $this->readDoc('custom.md'));
    }

    public function test_generated_scaffold_builds_successfully(): void {
        $this->artisan('manual:init')->assertSuccessful();

        $this->artisan('manual:build')
            ->expectsOutputToContain('Manual build complete')
            ->assertSuccessful();
    }
}
