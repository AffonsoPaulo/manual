<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Unit;

use ReflectionMethod;
use ServeraCloud\Manual\Services\ManualCache;
use ServeraCloud\Manual\Tests\TestCase;

final class ManualCacheTest extends TestCase
{
    public function test_registry_key_is_namespaced_per_application(): void
    {
        $cache = app(ManualCache::class);
        $method = new ReflectionMethod($cache, 'registryKey');
        $method->setAccessible(true);

        $registryKey = $method->invoke($cache);

        $this->assertStringStartsWith('manual:registry:', $registryKey);
        $this->assertNotSame('manual:registry', $registryKey);
    }

    public function test_key_is_collision_resistant_when_parts_contain_pipe_characters(): void
    {
        $cache = app(ManualCache::class);

        $keyA = $cache->key('manifest', 'a|b', 'c');
        $keyB = $cache->key('manifest', 'a', 'b|c');

        $this->assertNotSame($keyA, $keyB);
    }

    public function test_remember_falls_back_to_callback_when_store_is_unavailable(): void
    {
        $this->app['config']->set('manual.cache_store', 'nonexistent-driver');
        $cache = app(ManualCache::class);

        $calls = 0;
        $value = $cache->remember('some-key', function () use (&$calls): string {
            $calls++;

            return 'computed';
        });

        $this->assertSame('computed', $value);
        $this->assertSame(1, $calls);
    }

    public function test_clear_returns_zero_when_store_is_unavailable(): void
    {
        $this->app['config']->set('manual.cache_store', 'nonexistent-driver');
        $cache = app(ManualCache::class);

        $result = $cache->clear();

        $this->assertSame(0, $result);
    }

    public function test_remember_recomputes_when_cached_value_is_an_incomplete_class(): void
    {
        $cache = app(ManualCache::class);
        $key = $cache->key('manifest', 'legacy-cache');

        cache()->store('array')->put($key, unserialize('O:10:"GhostClass":0:{}'), 3600);

        $calls = 0;
        $value = $cache->remember($key, function () use (&$calls): array {
            $calls++;

            return ['rebuilt' => true];
        });

        $this->assertSame(['rebuilt' => true], $value);
        $this->assertSame(1, $calls);
        $this->assertSame(['rebuilt' => true], cache()->store('array')->get($key));
    }
}
