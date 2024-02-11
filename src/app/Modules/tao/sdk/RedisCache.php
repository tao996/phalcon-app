<?php

namespace app\Modules\tao\sdk;

use Phalcon\Cache\Exception\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;

class RedisCache implements CacheInterface
{
    private \Phalcon\Cache\Cache $cache;

    public function __construct()
    {
        $this->cache = cache();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        return $this->cache->set($key, $value, $ttl);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = $this->cache->getMultiple($keys, $default);
        return $values;
    }

    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        return $this->cache->setMultiple($values, $ttl);
    }

    public function deleteMultiple(iterable $keys): bool
    {
        return $this->cache->deleteMultiple($keys);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }
}