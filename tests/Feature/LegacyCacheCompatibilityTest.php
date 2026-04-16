<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Feature;

use ServeraCloud\Manual\Services\DocumentScanner;
use ServeraCloud\Manual\Services\ManualCache;
use ServeraCloud\Manual\Tests\TestCase;

final class LegacyCacheCompatibilityTest extends TestCase
{
    public function test_runtime_rebuilds_manifest_when_cached_entry_is_an_incomplete_class(): void
    {
        $this->writeDoc('index.md', "# Home\n\nBem-vindo.");

        app(DocumentScanner::class)->scan();

        $manifestKey = null;

        foreach (app(ManualCache::class)->trackedKeys() as $key) {
            if (str_starts_with($key, 'manual:manifest:')) {
                $manifestKey = $key;

                break;
            }
        }

        $this->assertIsString($manifestKey);

        cache()->store('array')->put(
            $manifestKey,
            unserialize('O:10:"GhostClass":0:{}'),
            3600,
        );

        $this->get('/manual')
            ->assertOk()
            ->assertSee('Home')
            ->assertSee('Bem-vindo.');
    }
}
