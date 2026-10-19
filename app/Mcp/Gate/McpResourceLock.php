<?php

namespace App\Mcp\Gate;

use Illuminate\Support\Facades\Cache;

class McpResourceLock
{
    const PREFIX = 'mcp_res_lock:';

    /**
     * Extract primary resource target UUID from tool arguments.
     */
    public static function extractResourceId(array $arguments): ?string
    {
        return $arguments['property_id']
            ?? $arguments['id']
            ?? $arguments['uuid']
            ?? $arguments['room_id']
            ?? null;
    }

    /**
     * Attempt to atomically acquire an exclusive mutex lock on a resource.
     * Returns true if lock was acquired, false if collision detected.
     */
    public function acquire(string $resourceId, string $holderId, int $ttlSeconds = 180): bool
    {
        $key = self::PREFIX . $resourceId;
        $payload = [
            'resource_id' => $resourceId,
            'holder' => $holderId,
            'locked_at' => now()->format('H:i:s'),
            'ttl' => $ttlSeconds,
        ];

        return Cache::add($key, $payload, $ttlSeconds);
    }

    /**
     * Check if a resource is currently locked. Returns lock info or null.
     */
    public function getLock(string $resourceId): ?array
    {
        return Cache::get(self::PREFIX . $resourceId);
    }

    /**
     * Release the lock for a specific resource.
     */
    public function release(string $resourceId): bool
    {
        return Cache::forget(self::PREFIX . $resourceId);
    }
}
