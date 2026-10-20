<?php

namespace App\Mcp\Server;

use App\Mcp\Contracts\ToolInterface;
use App\Mcp\Tools\Property\CreatePropertyTool;
use App\Mcp\Tools\Property\DeletePropertyTool;
use App\Mcp\Tools\Property\GetPropertyStatsTool;
use App\Mcp\Tools\Property\GetPropertyTool;
use App\Mcp\Tools\Property\ListPropertiesTool;
use App\Mcp\Tools\Property\UpdatePropertyTool;
use App\Models\McpServer;
use App\Models\McpToolCall;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Throwable;

class McpServerEngine
{
    public const PROTOCOL_VERSION = '2024-11-05';
    public const SERVER_NAME = 'quickflow-property-mcp';
    public const SERVER_VERSION = '1.0.0';

    protected ToolRegistry $registry;

    public function __construct(?ToolRegistry $registry = null)
    {
        $this->registry = $registry ?? new ToolRegistry();
        $this->registerDefaultTools();
    }

    public function getRegistry(): ToolRegistry
    {
        return $this->registry;
    }

    protected function registerDefaultTools(): void
    {
        $this->registry->register(new GetPropertyStatsTool());
        $this->registry->register(new ListPropertiesTool());
        $this->registry->register(new GetPropertyTool());
        $this->registry->register(new CreatePropertyTool());
        $this->registry->register(new UpdatePropertyTool());
        $this->registry->register(new DeletePropertyTool());
    }

    /**
     * Handle incoming JSON-RPC request. Returns null for notifications that require no response.
     */
    public function handle(array $request): ?array
    {
        $id = $request['id'] ?? null;
        $method = $request['method'] ?? null;

        if (!isset($request['jsonrpc']) || $request['jsonrpc'] !== '2.0' || !$method) {
            return JsonRpcResponse::error($id, JsonRpcResponse::INVALID_REQUEST, 'Invalid JSON-RPC 2.0 request.');
        }

        // Handle Notifications
        if ($method === 'notifications/initialized') {
            return null; // Notifications don't return response
        }

        // Method router
        return match ($method) {
            'initialize' => $this->handleInitialize($id, $request['params'] ?? []),
            'ping' => JsonRpcResponse::success($id, (object) []),
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolCall($id, $request['params'] ?? []),
            default => JsonRpcResponse::error($id, JsonRpcResponse::METHOD_NOT_FOUND, "Method '{$method}' not supported."),
        };
    }

    protected function handleInitialize(mixed $id, array $params): array
    {
        return JsonRpcResponse::success($id, [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => [
                'tools' => [
                    'listChanged' => false,
                ],
            ],
            'serverInfo' => [
                'name' => self::SERVER_NAME,
                'version' => self::SERVER_VERSION,
            ],
        ]);
    }

    protected function handleToolsList(mixed $id): array
    {
        return JsonRpcResponse::success($id, [
            'tools' => $this->registry->toMcpToolList(),
        ]);
    }

    protected function handleToolCall(mixed $id, array $params): array
    {
        $toolName = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (!$toolName) {
            return JsonRpcResponse::error($id, JsonRpcResponse::INVALID_PARAMS, 'Parameter "name" is required.');
        }

        $tool = $this->registry->getTool($toolName);
        if (!$tool) {
            return JsonRpcResponse::error($id, JsonRpcResponse::METHOD_NOT_FOUND, "Tool '{$toolName}' not found.");
        }

        $startTime = microtime(true);
        $hasAcquiredLock = false;
        $targetResourceId = null;
        $resourceLock = null;

        try {
            // -2. Enterprise Multi-Tenant Ingress Defense: Tenant Capability Guard (Data Boundary & Anti-BOLA)
            if (\App\Mcp\Gate\McpTenantContext::isEnabled()) {
                $tenantValidation = \App\Mcp\Gate\McpTenantContext::validate();
                if (!$tenantValidation['valid']) {
                    $durationMs = round((microtime(true) - $startTime) * 1000, 2);
                    $errorMsg = $tenantValidation['message'];

                    $errorPayload = [
                        'error' => $errorMsg,
                        'guard' => 'TENANT_CAPABILITY_INGRESS',
                        'tool' => $toolName,
                        '_meta' => [
                            'duration_ms' => $durationMs,
                            'status' => 'UNAUTHORIZED',
                            'provided_tenant' => $tenantValidation['provided_tenant'] ?? 'none',
                        ],
                    ];

                    $this->logToolCall($toolName, $arguments, $errorPayload, 'error', $durationMs);

                    return JsonRpcResponse::success($id, [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $errorMsg,
                            ],
                        ],
                        'isError' => true,
                    ]);
                }
            }

            // -1. Enterprise Infrastructure Defense: Token Bucket Rate Limiter (Anti-DDoS & Noisy Neighbor Isolation)
            if (\App\Mcp\Gate\McpRateLimiter::isEnabled()) {
                // In a multi-tenant public tunnel, bind the Token Bucket to the active tenant capability.
                // This guarantees Tenant Alpha's burst traffic never starves Tenant Beta (Anti-Noisy Neighbor).
                $activeTenant = \App\Mcp\Gate\McpTenantContext::getActiveTenant();
                $clientIdentifier = $activeTenant ? 'tenant_' . $activeTenant['slug'] : 'mcp_tunnel_ingress';
                $tenantCapacity = $activeTenant['rate_limit_capacity'] ?? \App\Mcp\Gate\McpRateLimiter::getCapacity();

                $rateResult = \App\Mcp\Gate\McpRateLimiter::check($clientIdentifier, $tenantCapacity);
                if (!$rateResult['allowed']) {
                    $durationMs = round((microtime(true) - $startTime) * 1000, 2);
                    $retryAfter = $rateResult['retry_after'];
                    $tenantName = $activeTenant['name'] ?? null;
                    $errorMsg = \App\Mcp\Gate\McpRateLimiter::getRejectionMessage($retryAfter, $tenantName);

                    $errorPayload = [
                        'error' => $errorMsg,
                        'guard' => 'TOKEN_BUCKET_RATE_LIMITER',
                        'tool' => $toolName,
                        '_meta' => [
                            'duration_ms' => $durationMs,
                            'policy' => 'TENANT_TOKEN_BUCKET',
                            'retry_after_seconds' => $retryAfter,
                            'capacity' => $rateResult['capacity'],
                            'remaining' => 0,
                            'tenant' => $activeTenant ? [
                                'slug' => strtoupper($activeTenant['slug']),
                                'name' => $activeTenant['name'],
                            ] : null,
                        ],
                    ];

                    $this->logToolCall($toolName, $arguments, $errorPayload, 'error', $durationMs);

                    return JsonRpcResponse::success($id, [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $errorMsg,
                            ],
                        ],
                        'isError' => true,
                    ]);
                }
            }

            // 0. Enterprise Database Defense: Read-Only Ingress Guard (Least Privilege)
            if (\App\Mcp\Gate\McpReadOnlyGuard::isEnabled() && \App\Mcp\Gate\McpReadOnlyGuard::isMutation($toolName, $tool)) {
                $durationMs = round((microtime(true) - $startTime) * 1000, 2);
                $errorMsg = \App\Mcp\Gate\McpReadOnlyGuard::getRejectionMessage($toolName);

                $errorPayload = [
                    'error' => $errorMsg,
                    'guard' => 'READ_ONLY_INGRESS',
                    'tool' => $toolName,
                    '_meta' => [
                        'duration_ms' => $durationMs,
                        'policy' => 'LEAST_PRIVILEGE',
                    ],
                ];

                $this->logToolCall($toolName, $arguments, $errorPayload, 'error', $durationMs);

                return JsonRpcResponse::success($id, [
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $errorMsg,
                        ],
                    ],
                    'isError' => true,
                ]);
            }

            // 1. Enterprise Concurrency Policy: Distributed Mutex Lock
            $resourceLock = new \App\Mcp\Gate\McpResourceLock();
            $targetResourceId = \App\Mcp\Gate\McpResourceLock::extractResourceId($arguments);

            // 2. Enterprise Security Policy: Human-in-the-Loop Approval Gate
            $gate = new \App\Mcp\Gate\McpApprovalGate();
            $auth = null;
            $isMutation = $gate->requiresApproval($toolName, $tool, $arguments);

            if ($isMutation && $targetResourceId) {
                $existingLock = $resourceLock->getLock($targetResourceId);
                if ($existingLock) {
                    $durationMs = round((microtime(true) - $startTime) * 1000, 2);
                    $errorMsg = "MUTEX COLLISION BLOCKED: Resource '{$targetResourceId}' is locked by session [{$existingLock['holder']}]. Concurrent mutation aborted to prevent race condition.";
                    $errorPayload = [
                        'error' => $errorMsg,
                        'collision' => true,
                        'held_by' => $existingLock['holder'],
                        'locked_at' => $existingLock['locked_at'],
                    ];
                    $this->logToolCall($toolName, $arguments, $errorPayload, 'collision_blocked', $durationMs);

                    return JsonRpcResponse::success($id, [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $errorMsg,
                            ],
                        ],
                        'isError' => true,
                    ]);
                }

                $lockHolderId = 'AGENT-' . strtoupper(\Illuminate\Support\Str::random(4));
                $resourceLock->acquire($targetResourceId, $lockHolderId, 180);
                $hasAcquiredLock = true;
            }

            if ($isMutation) {
                $auth = $gate->requestAuthorization($toolName, $arguments, timeoutSeconds: 180);

                if (!$auth['approved']) {
                    if ($hasAcquiredLock && $targetResourceId) {
                        $resourceLock->release($targetResourceId);
                        $hasAcquiredLock = false;
                    }

                    $durationMs = round((microtime(true) - $startTime) * 1000, 2);
                    $errorMsg = "SECURITY GATE BLOCKED: {$auth['reason']} [Ticket: {$auth['ticket']}]";
                    $errorPayload = [
                        'error' => $errorMsg,
                        'gate_status' => $auth['status'],
                        'ticket' => $auth['ticket'],
                        'target' => $auth['target_summary'],
                    ];
                    $this->logToolCall($toolName, $arguments, $errorPayload, 'error', $durationMs);

                    return JsonRpcResponse::success($id, [
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $errorMsg,
                            ],
                        ],
                        'isError' => true,
                    ]);
                }
            }

            $result = $tool->execute($arguments);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            if (is_array($result)) {
                if ($auth && $auth['approved']) {
                    $result['_gate_authorization'] = [
                        'status' => 'APPROVED_BY_HUMAN',
                        'ticket' => $auth['ticket'],
                        'authorization_latency_ms' => $auth['duration_ms'],
                    ];
                }

                if (isset($rateResult) && is_array($rateResult)) {
                    $result['_meta']['token_bucket'] = [
                        'remaining' => $rateResult['remaining'],
                        'capacity' => $rateResult['capacity'],
                        'was_refilled' => $rateResult['was_refilled'],
                        'refilled_tokens' => $rateResult['refilled_tokens'],
                    ];
                }

                if (\App\Mcp\Gate\McpTenantContext::hasActiveTenant()) {
                    $activeTenant = \App\Mcp\Gate\McpTenantContext::getActiveTenant();
                    $result['_meta']['tenant'] = [
                        'slug' => strtoupper($activeTenant['slug']),
                        'name' => $activeTenant['name'],
                        'id' => $activeTenant['uuid'],
                    ];
                }
            }

            $this->logToolCall($toolName, $arguments, $result, 'success', $durationMs);

            return JsonRpcResponse::success($id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'isError' => false,
            ]);
        } catch (Throwable $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);
            $errorPayload = ['error' => $e->getMessage()];
            $this->logToolCall($toolName, $arguments, $errorPayload, 'error', $durationMs);

            return JsonRpcResponse::success($id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Error: ' . $e->getMessage(),
                    ],
                ],
                'isError' => true,
            ]);
        } finally {
            if ($hasAcquiredLock && $targetResourceId) {
                $resourceLock->release($targetResourceId);
            }
        }
    }

    protected function logToolCall(string $toolName, array $arguments, mixed $response, string $status, float $durationMs = 0.0): void
    {
        try {
            $serverId = $this->resolveMcpServerId($arguments['tenant_id'] ?? null);
            if ($serverId) {
                $sanitizedArgs = $this->sanitizeArguments($arguments);

                if (is_array($response)) {
                    $existingMeta = $response['_meta'] ?? [];
                    $tenantInfo = \App\Mcp\Gate\McpTenantContext::hasActiveTenant()
                        ? [
                            'slug' => strtoupper(\App\Mcp\Gate\McpTenantContext::getActiveTenantSlug()),
                            'name' => \App\Mcp\Gate\McpTenantContext::getActiveTenantName(),
                        ]
                        : null;

                    $response['_meta'] = array_merge($existingMeta, [
                        'duration_ms' => $durationMs,
                        'logged_at' => now()->toIso8601String(),
                        'tenant' => $tenantInfo,
                    ]);
                }

                McpToolCall::create([
                    'mcp_server_id' => $serverId,
                    'tool_name' => $toolName,
                    'arguments' => $sanitizedArgs,
                    'response' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'status' => $status,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to log MCP tool call: ' . $e->getMessage());
        }
    }

    /**
     * Enterprise Guardrail: Redact sensitive credentials before storing in audit log.
     */
    protected function sanitizeArguments(array $arguments): array
    {
        $sensitiveKeys = ['password', 'token', 'api_key', 'secret', 'auth', 'access_token', 'private_key'];

        foreach ($arguments as $key => $value) {
            if (is_array($value)) {
                $arguments[$key] = $this->sanitizeArguments($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys, true)) {
                $arguments[$key] = '********';
            }
        }

        return $arguments;
    }

    protected function resolveMcpServerId(?string $tenantId = null): ?string
    {
        $tenant = null;
        if ($tenantId) {
            $tenant = Tenant::where('uuid', $tenantId)->first();
        }

        if (!$tenant) {
            $tenant = Tenant::first();
        }

        if (!$tenant) {
            return null;
        }

        $server = McpServer::where('tenant_id', $tenant->uuid)
            ->where('server_name', self::SERVER_NAME)
            ->first();

        if (!$server) {
            $server = McpServer::create([
                'tenant_id' => $tenant->uuid,
                'server_name' => self::SERVER_NAME,
                'endpoint_url' => url('/api/mcp'),
                'permitted_tools' => array_keys($this->registry->getTools()),
            ]);
        }

        return $server->uuid;
    }
}
