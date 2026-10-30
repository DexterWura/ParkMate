<?php

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache as LaravelCache;

class CacheService
{
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        return LaravelCache::remember($key, $ttl, $callback);
    }

    public function forget(string $key): bool
    {
        return LaravelCache::forget($key);
    }

    public function flush(): bool
    {
        return LaravelCache::flush();
    }
}
