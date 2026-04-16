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
