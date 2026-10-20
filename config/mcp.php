<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Authentication Key (Bearer Token)
    |--------------------------------------------------------------------------
    |
    | When configured, all incoming MCP requests from cloud AI agents (ChatGPT,
    | Claude) or external clients must supply this secret key via:
    |   - Header: Authorization: Bearer <MCP_API_KEY>
    |   - Header: X-MCP-API-KEY: <MCP_API_KEY>
    |
    */
    'api_key' => env('MCP_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Enforce Authentication Shield
    |--------------------------------------------------------------------------
    |
    | When true and an api_key is configured, unauthenticated requests are
    | immediately rejected with 401 Unauthorized in sub-5ms.
    |
    */
    'auth_enabled' => env('MCP_AUTH_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Read-Only Ingress Guard (Least Privilege Database Protection)
    |--------------------------------------------------------------------------
    |
    | When true, all mutation tools (create, update, delete) are strictly
    | blocked on public tunnel ingress to prevent remote database tampering.
    |
    */
    'read_only' => env('MCP_READ_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Token Bucket Rate Limiting (Infrastructure Anti-DDoS Defense)
    |--------------------------------------------------------------------------
    |
    | Prevents public AI bot loops, burst requests, and distributed spam
    | from overwhelming local server resources and databases.
    |
    */
    'rate_limit_enabled' => env('MCP_RATE_LIMIT_ENABLED', true),
    'rate_limit_capacity' => (int) env('MCP_RATE_LIMIT_CAPACITY', 3), // Max burst tokens (3 tokens for 3-prompt demo)
    'rate_limit_refill_rate' => (float) env('MCP_RATE_LIMIT_REFILL_RATE', 0.016),
    'rate_limit_cooldown' => (int) env('MCP_RATE_LIMIT_COOLDOWN', 30), // Penalty cooldown window (30s)

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Capability Ingress & Data Isolation
    |--------------------------------------------------------------------------
    |
    | Enforces strict cryptographic/scoped tenant boundary isolation across
    | remote public MCP tunnels. Prevents cross-tenant data leakage (BOLA).
    |
    */
    'tenant_isolation_enabled' => env('MCP_TENANT_ISOLATION_ENABLED', true),
];
