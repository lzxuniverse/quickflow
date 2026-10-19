<?php

namespace App\Mcp\Http\Middleware;

use App\Mcp\Server\JsonRpcResponse;
use App\Models\McpToolCall;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class McpAuthenticate
{
    /**
     * Handle an incoming request for the MCP endpoint.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // 1. Always allow CORS Preflight OPTIONS requests without authentication
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $expectedKey = config('mcp.api_key', env('MCP_API_KEY'));
        $authEnabled = config('mcp.auth_enabled', env('MCP_AUTH_ENABLED', true));

        // 2. If Auth Shield is disabled or no key is configured, pass through (open dev mode)
        if (!$authEnabled || empty($expectedKey)) {
            return $next($request);
        }

        // 3. Extract Bearer token, API key header, or URL Query Parameter (for ChatGPT ingress)
        $token = $request->bearerToken()
            ?: $request->header('X-MCP-API-KEY')
            ?: $request->header('X-API-KEY')
            ?: $request->query('token')
            ?: $request->query('api_key')
            ?: $request->query('key');

        // 4. Validate Token with constant-time comparison (mitigate timing attacks)
        if (!$token || !hash_equals((string) $expectedKey, (string) $token)) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            // Log security incident to McpToolCall for live terminal observability (mcp:tail)
            try {
                $serverId = \App\Models\McpServer::value('uuid');
                McpToolCall::create([
                    'mcp_server_id' => $serverId,
                    'tool_name' => 'auth:shield',
                    'arguments' => [
                        'ip' => $request->ip(),
                        'method' => $request->method(),
                        'path' => $request->path(),
                        'token_provided' => $token ? substr($token, 0, 8) . '...' : 'none',
                    ],
                    'response' => json_encode([
                        'error' => '401 Unauthorized: Invalid or missing Bearer token',
                        '_meta' => [
                            'duration_ms' => $durationMs,
                            'auth_status' => 'REJECTED',
                        ],
                    ], JSON_UNESCAPED_SLASHES),
                    'status' => 'error',
                ]);
            } catch (\Throwable $e) {
                // Silently bypass logging failure so security rejection is never compromised
            }

            // Return JSON-RPC compliant error if POST JSON request, otherwise standard JSON error
            $isJsonRpc = $request->isMethod('POST') && $request->isJson();
            $requestId = $isJsonRpc ? ($request->json('id') ?? null) : null;

            $errorPayload = $isJsonRpc
                ? JsonRpcResponse::error($requestId, -32001, 'Unauthorized: Invalid or missing Bearer token.')
                : [
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'Unauthorized: Invalid or missing Bearer token.',
                    'hint' => 'Provide a valid Bearer token via Authorization: Bearer <TOKEN> header.',
                ];

            return response()->json($errorPayload, 401, [
                'WWW-Authenticate' => 'Bearer realm="QuickFlow MCP Server", error="invalid_token"',
                'X-MCP-Auth' => 'rejected',
                'X-MCP-Shield-Latency' => "{$durationMs}ms",
            ]);
        }

        /** @var Response $response */
        $response = $next($request);

        // Append verification header
        $response->headers->set('X-MCP-Auth', 'verified');

        return $response;
    }
}
