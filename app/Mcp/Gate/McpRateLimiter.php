<?php

namespace App\Mcp\Gate;

use Illuminate\Support\Facades\Cache;

class McpRateLimiter
{
    public static function isEnabled(): bool
    {
        return (bool) config('mcp.rate_limit_enabled', env('MCP_RATE_LIMIT_ENABLED', true));
    }

    public static function getCapacity(): int
    {
        return (int) config('mcp.rate_limit_capacity', env('MCP_RATE_LIMIT_CAPACITY', 5));
    }

    public static function getRefillRate(): float
    {
        return (float) config('mcp.rate_limit_refill_rate', env('MCP_RATE_LIMIT_REFILL_RATE', 0.5));
    }

    public static function getCooldownSeconds(): int
    {
        return (int) config('mcp.rate_limit_cooldown', env('MCP_RATE_LIMIT_COOLDOWN', 30));
    }

    /**
     * Evaluate Token Bucket for a given client identifier (Tenant-scoped or Tunnel-scoped).
     *
     * @param string $identifier Client IP, Tenant Slug/UUID, or Tunnel Session
     * @param int|null $customCapacity Override capacity per tenant tier
     * @return array [allowed => bool, remaining => int, retry_after => int, capacity => int]
     */
    public static function check(string $identifier = 'mcp_tunnel_ingress', ?int $customCapacity = null): array
    {
        if (!self::isEnabled()) {
            return [
                'allowed' => true,
                'remaining' => 999,
                'retry_after' => 0,
                'capacity' => 999,
            ];
        }

        $capacity = $customCapacity ?? self::getCapacity();
        $cooldownSeconds = self::getCooldownSeconds(); // Configurable penalty cooldown window (e.g. 60s/120s)
        $cacheKey = "mcp:rate_limit:bucket:" . md5($identifier);

        $now = microtime(true);
        $state = Cache::get($cacheKey);

        if (!$state || !is_array($state)) {
            $tokens = (float) $capacity;
            $isThrottled = false;
            $throttledAt = 0.0;
            $lastUpdated = $now;
        } else {
            $tokens = (float) ($state['tokens'] ?? $capacity);
            $isThrottled = (bool) ($state['is_throttled'] ?? false);
            $throttledAt = (float) ($state['throttled_at'] ?? 0.0);
            $lastUpdated = (float) ($state['last_updated'] ?? $now);
        }

        // Case A: Client is currently in 30s Penalty Cooldown
        if ($isThrottled) {
            $elapsed = $now - $throttledAt;
            if ($elapsed < $cooldownSeconds) {
                $retryAfter = (int) ceil($cooldownSeconds - $elapsed);
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'retry_after' => max(1, $retryAfter),
                    'capacity' => $capacity,
                    'was_refilled' => false,
                    'refilled_tokens' => 0,
                ];
            }

            // Cooldown expired! Auto-refill bucket back to capacity
            $tokens = (float) $capacity;
            $isThrottled = false;
            $throttledAt = 0.0;
            $wasRefilled = true;
        } else {
            // Case B: Client is NOT throttled. Apply continuous token regeneration & idle restore!
            $elapsedSinceLast = $now - $lastUpdated;
            if ($elapsedSinceLast >= $cooldownSeconds) {
                // If idle for >= cooldown window, automatically restore bucket to 100% capacity!
                $tokens = (float) $capacity;
                $wasRefilled = true;
            } elseif ($tokens < $capacity && $elapsedSinceLast > 0) {
                $refillRate = self::getRefillRate();
                $added = $elapsedSinceLast * $refillRate;
                $newTokens = min((float) $capacity, $tokens + $added);
                $wasRefilled = ($newTokens >= (float) $capacity && $tokens < (float) $capacity);
                $tokens = $newTokens;
            } else {
                $wasRefilled = false;
            }
        }

        // Case C: Normal token consumption (In-quota) - Deduct EXACTLY 1.0 token
        if ($tokens >= 1.0) {
            $tokens -= 1.0;
            Cache::put($cacheKey, [
                'tokens' => $tokens,
                'is_throttled' => false,
                'throttled_at' => 0.0,
                'last_updated' => $now,
            ], now()->addMinutes(10));

            return [
                'allowed' => true,
                'remaining' => (int) floor($tokens),
                'retry_after' => 0,
                'capacity' => $capacity,
                'was_refilled' => $wasRefilled,
                'refilled_tokens' => $wasRefilled ? $capacity : 0,
            ];
        }

        // Case C: Tokens exhausted -> TRIGGER 30s PENALTY COOLDOWN!
        $isThrottled = true;
        $throttledAt = $now;

        Cache::put($cacheKey, [
            'tokens' => 0.0,
            'is_throttled' => true,
            'throttled_at' => $throttledAt,
            'last_updated' => $now,
        ], now()->addMinutes(10));

        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => $cooldownSeconds,
            'capacity' => $capacity,
            'was_refilled' => false,
            'refilled_tokens' => 0,
        ];
    }

    public static function getRejectionMessage(int $retryAfter, ?string $tenantName = null): string
    {
        $capacity = self::getCapacity();
        if ($tenantName) {
            return "429 TOO MANY REQUESTS: Tenant quota exhausted for [{$tenantName}] ({$capacity} tokens). Cooldown penalty active: please wait {$retryAfter} seconds before sending the next prompt.";
        }
        return "RATE LIMIT EXCEEDED: Infrastructure capacity reached ({$capacity} tokens). Cooldown penalty active: please wait {$retryAfter} seconds before sending the next prompt.";
    }

    /**
     * Get real-time status of Token Bucket (used by mcp:tail countdown tracking).
     */
    public static function getStatus(string $identifier = 'mcp_tunnel_ingress', ?int $customCapacity = null): array
    {
        $capacity = $customCapacity ?? self::getCapacity();
        $cooldownSeconds = self::getCooldownSeconds();
        $cacheKey = "mcp:rate_limit:bucket:" . md5($identifier);

        $now = microtime(true);
        $state = Cache::get($cacheKey);

        if (!$state || !is_array($state)) {
            return [
                'tokens' => (float) $capacity,
                'capacity' => $capacity,
                'is_throttled' => false,
                'seconds_remaining' => 0,
            ];
        }

        $tokens = (float) ($state['tokens'] ?? $capacity);
        $isThrottled = (bool) ($state['is_throttled'] ?? false);
        $throttledAt = (float) ($state['throttled_at'] ?? 0.0);
        $lastUpdated = (float) ($state['last_updated'] ?? $now);

        if ($isThrottled) {
            $elapsed = $now - $throttledAt;
            if ($elapsed < $cooldownSeconds) {
                return [
                    'tokens' => 0.0,
                    'capacity' => $capacity,
                    'is_throttled' => true,
                    'seconds_remaining' => (int) ceil($cooldownSeconds - $elapsed),
                ];
            }
            $tokens = (float) $capacity;
            $isThrottled = false;
        } else {
            $elapsedSinceLast = $now - $lastUpdated;
            if ($elapsedSinceLast >= $cooldownSeconds) {
                $tokens = (float) $capacity;
            } elseif ($tokens < $capacity && $elapsedSinceLast > 0) {
                $tokens = min((float) $capacity, $tokens + ($elapsedSinceLast * self::getRefillRate()));
            }
        }

        return [
            'tokens' => $tokens,
            'capacity' => $capacity,
            'is_throttled' => false,
            'seconds_remaining' => 0,
        ];
    }

    /**
     * Reset rate limiter for testing (global and all known tenants).
     */
    public static function reset(?string $identifier = null): void
    {
        if ($identifier) {
            Cache::forget("mcp:rate_limit:bucket:" . md5($identifier));
            return;
        }

        Cache::forget("mcp:rate_limit:bucket:" . md5('mcp_tunnel_ingress'));
        Cache::forget("mcp:rate_limit:bucket:" . md5('tunnel_global'));
        Cache::forget("mcp:rate_limit:bucket:" . md5('tenant_alpha'));
        Cache::forget("mcp:rate_limit:bucket:" . md5('tenant_beta'));
    }
}
