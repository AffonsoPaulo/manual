<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Feature;

use ServeraCloud\Manual\Services\ManualCache;
use ServeraCloud\Manual\Tests\TestCase;

final class CommandsTest extends TestCase {
    public function test_build_command_warms_page_and_search_cache(): void {
        $this->writeDoc('index.md', "# Home\n\nBem-vindo.");
        $this->writeDoc('guide/install.md', "# Install\n\nConteúdo.");

        $this->artisan('manual:build')
            ->expectsOutputToContain('Manual build complete')
            ->assertSuccessful();

        $trackedKeys = app(ManualCache::class)->trackedKeys();

        $this->assertNotEmpty($trackedKeys);
    }

    public function test_clear_command_removes_cache_entries(): void {
        $this->writeDoc('index.md', "# Home\n\nBem-vindo.");

        $this->artisan('manual:build')->assertSuccessful();
        $this->assertNotEmpty(app(ManualCache::class)->trackedKeys());

        $this->artisan('manual:clear')
            ->expectsOutputToContain('Manual cache cleared')
            ->assertSuccessful();

        $this->assertSame([], app(ManualCache::class)->trackedKeys());
    }
}
