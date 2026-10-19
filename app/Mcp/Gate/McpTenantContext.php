<?php

namespace App\Mcp\Gate;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class McpTenantContext
{
    protected static ?array $activeTenant = null;
    protected static ?string $providedIdentifier = null;
    protected static bool $initialized = false;

    /**
     * Known Enterprise Tenant Registry (Capability Slugs).
     */
    protected static array $registry = [
        'alpha' => [
            'uuid' => '12052866-68ed-45ec-a16c-11600ddd4510',
            'name' => 'Alpha Hospitality Group',
            'slug' => 'alpha',
            'country' => 'France',
            'primary_market' => 'Europe / France',
            'token' => 'cap_alpha_paris_99',
        ],
        'beta' => [
            'uuid' => '434cfa16-a1be-4178-a886-487723025dd8',
            'name' => 'Beta Grand Resorts',
            'slug' => 'beta',
            'country' => 'Germany',
            'primary_market' => 'Americas / Nordics',
            'token' => 'cap_beta_berlin_88',
        ],
    ];

    /**
     * Check if Tenant Isolation Guard is enabled.
     */
    public static function isEnabled(): bool
    {
        return (bool) config('mcp.tenant_isolation_enabled', env('MCP_TENANT_ISOLATION_ENABLED', true));
    }

    /**
     * Resolve and bind tenant context from incoming HTTP Request.
     */
    public static function resolveFromRequest(Request $request, ?string $routeTenant = null): void
    {
        self::$initialized = true;

        $identifier = $routeTenant
            ?: $request->route('tenant')
            ?: $request->query('tenant')
            ?: $request->query('tenant_id')
            ?: $request->query('token')
            ?: $request->header('X-MCP-TENANT')
            ?: $request->header('X-MCP-TENANT-ID');

        self::$providedIdentifier = $identifier ? trim((string) $identifier) : null;

        if (!self::$providedIdentifier) {
            self::$activeTenant = null;
            return;
        }

        // 1. Direct match by Slug or Token
        $key = strtolower(self::$providedIdentifier);
        if (isset(self::$registry[$key])) {
            self::$activeTenant = self::$registry[$key];
            return;
        }

        // 2. Match by token or UUID
        foreach (self::$registry as $tenant) {
            if ($tenant['uuid'] === self::$providedIdentifier || $tenant['token'] === self::$providedIdentifier) {
                self::$activeTenant = $tenant;
                return;
            }
        }

        self::$activeTenant = null;
    }

    /**
     * Set active tenant manually (e.g., in tests or artisan commands).
     */
    public static function setActiveTenant(?string $slugOrUuid): void
    {
        self::$initialized = true;
        self::$providedIdentifier = $slugOrUuid;

        if (!$slugOrUuid) {
            self::$activeTenant = null;
            return;
        }

        $key = strtolower(trim($slugOrUuid));
        if (isset(self::$registry[$key])) {
            self::$activeTenant = self::$registry[$key];
            return;
        }

        foreach (self::$registry as $tenant) {
            if ($tenant['uuid'] === $slugOrUuid || $tenant['token'] === $slugOrUuid) {
                self::$activeTenant = $tenant;
                return;
            }
        }

        self::$activeTenant = null;
    }

    /**
     * Validate incoming tenant capability.
     * Returns ['valid' => bool, 'message' => string, 'tenant' => ?array].
     */
    public static function validate(): array
    {
        if (!self::isEnabled()) {
            return ['valid' => true, 'tenant' => null, 'message' => 'Tenant isolation disabled.'];
        }

        // When connecting via root unpartitioned /mcp, allow passthrough without active tenant (Unscoped raw system - 196 properties leak)
        if (!self::$providedIdentifier) {
            return [
                'valid' => true,
                'tenant' => null,
                'message' => 'Raw unpartitioned MCP ingress (Global shared mode).',
            ];
        }

        if (!self::$activeTenant) {
            return [
                'valid' => false,
                'provided_tenant' => self::$providedIdentifier,
                'message' => "401 UNAUTHORIZED: Untrusted or Revoked Tenant Capability Ingress ['" . htmlspecialchars(self::$providedIdentifier) . "']. Access Denied.",
            ];
        }

        return [
            'valid' => true,
            'tenant' => self::$activeTenant,
            'message' => 'Authorized tenant capability verified.',
        ];
    }

    /**
     * Check if a valid tenant is currently bound.
     */
    public static function hasActiveTenant(): bool
    {
        return self::$activeTenant !== null;
    }

    /**
     * Get active tenant UUID.
     */
    public static function getActiveTenantId(): ?string
    {
        return self::$activeTenant['uuid'] ?? null;
    }

    /**
     * Get active tenant Slug.
     */
    public static function getActiveTenantSlug(): ?string
    {
        return self::$activeTenant['slug'] ?? null;
    }

    /**
     * Get active tenant Name.
     */
    public static function getActiveTenantName(): ?string
    {
        return self::$activeTenant['name'] ?? null;
    }

    /**
     * Get active tenant metadata array.
     */
    public static function getActiveTenant(): ?array
    {
        return self::$activeTenant;
    }

    /**
     * Reset context.
     */
    public static function reset(): void
    {
        self::$activeTenant = null;
        self::$providedIdentifier = null;
        self::$initialized = false;
    }
}
