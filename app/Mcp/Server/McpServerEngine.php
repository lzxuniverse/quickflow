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
        $this->registry->register(new ListPropertiesTool());
        $this->registry->register(new GetPropertyTool());
        $this->registry->register(new CreatePropertyTool());
        $this->registry->register(new UpdatePropertyTool());
        $this->registry->register(new DeletePropertyTool());
        $this->registry->register(new GetPropertyStatsTool());
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

        try {
            $result = $tool->execute($arguments);

            $this->logToolCall($toolName, $arguments, $result, 'success');

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
            $errorPayload = ['error' => $e->getMessage()];
            $this->logToolCall($toolName, $arguments, $errorPayload, 'error');

            return JsonRpcResponse::success($id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Error: ' . $e->getMessage(),
                    ],
                ],
                'isError' => true,
            ]);
        }
    }

    protected function logToolCall(string $toolName, array $arguments, mixed $response, string $status): void
    {
        try {
            $serverId = $this->resolveMcpServerId($arguments['tenant_id'] ?? null);
            if ($serverId) {
                McpToolCall::create([
                    'mcp_server_id' => $serverId,
                    'tool_name' => $toolName,
                    'arguments' => $arguments,
                    'response' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'status' => $status,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to log MCP tool call: ' . $e->getMessage());
        }
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
