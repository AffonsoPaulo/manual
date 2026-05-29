<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Services;

use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use stdClass;

final class ManualCache
{
    public function __construct(
        private readonly CacheFactory $cacheFactory,
        private readonly ConfigRepository $config,
    ) {
    }

    public function remember(string $key, Closure $callback): mixed
    {
        $ttl = $this->ttl();

        if ($ttl !== null && $ttl <= 0) {
            return $callback();
        }

        try {
            $store = $this->store();
            $missing = new stdClass();
            $value = $store->get($key, $missing);

            if ($value !== $missing) {
                if (! $this->containsIncompleteClass($value)) {
                    $this->track($key);

                    return $value;
                }

                $store->forget($key);
            }

            $value = $callback();

            if ($ttl === null) {
                $store->forever($key, $value);
            } else {
                $store->put($key, $value, $ttl);
            }

            $this->track($key);

            return $value;
        } catch (\Throwable $exception) {
            logger()->warning('Manual cache store unavailable, bypassing cache.', [
                'key' => $key,
                'error' => $exception->getMessage(),
            ]);

            return $callback();
        }
    }

    public function key(string $prefix, string ...$parts): string
    {
        return 'manual:' . $prefix . ':' . sha1((string) json_encode($parts));
    }

    /**
     * @return list<string>
     */
    public function trackedKeys(): array
    {
        return array_values(array_keys((array) $this->store()->get($this->registryKey(), [])));
    }

    public function clear(): int
    {
        try {
            $store = $this->store();
            $keys = $this->trackedKeys();

            foreach ($keys as $key) {
                $store->forget($key);
            }

            $store->forget($this->registryKey());

            return count($keys);
        } catch (\Throwable $exception) {
            logger()->warning('Manual cache store unavailable, clear skipped.', [
                'error' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    private function store(): CacheRepository
    {
        $store = $this->config->get('manual.cache_store');

        return $store === null || $store === ''
            ? $this->cacheFactory->store()
            : $this->cacheFactory->store((string) $store);
    }

    private function ttl(): ?int
    {
        $ttl = $this->config->get('manual.cache_ttl', 3600);

        return $ttl === null ? null : (int) $ttl;
    }

    private function track(string $key): void
    {
        $store = $this->store();
        $registryKey = $this->registryKey();
        $keys = (array) $store->get($registryKey, []);
        $keys[$key] = true;
        $store->forever($registryKey, $keys);
    }

    private function containsIncompleteClass(mixed $value, array &$seen = []): bool
    {
        if ($value instanceof \__PHP_Incomplete_Class) {
            return true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsIncompleteClass($item, $seen)) {
                    return true;
                }
            }

            return false;
        }

        if (! is_object($value)) {
            return false;
        }

        $objectId = spl_object_id($value);

        if (isset($seen[$objectId])) {
            return false;
        }

        $seen[$objectId] = true;

        foreach ((array) $value as $property) {
            if ($this->containsIncompleteClass($property, $seen)) {
                return true;
            }
        }

        return false;
    }

    private function registryKey(): string
    {
        return 'manual:registry:' . sha1(base_path());
    }
}
