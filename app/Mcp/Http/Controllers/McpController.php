<?php

namespace App\Mcp\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mcp\Server\JsonRpcResponse;
use App\Mcp\Server\McpServerEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class McpController extends Controller
{
    protected McpServerEngine $engine;

    public function __construct(McpServerEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Handle incoming HTTP JSON-RPC MCP requests for a specific scoped tenant.
     */
    public function handleTenant(Request $request, string $tenant): JsonResponse|Response
    {
        return $this->handle($request, $tenant);
    }

    /**
     * Handle incoming HTTP JSON-RPC MCP requests.
     */
    public function handle(Request $request, ?string $tenant = null): JsonResponse|Response
    {
        @set_time_limit(180);

        \App\Mcp\Gate\McpTenantContext::resolveFromRequest($request, $tenant);

        // Handle GET, DELETE, HEAD health-checks or platform de-registration pings
        if (!$request->isMethod('POST')) {
            $tenantMeta = \App\Mcp\Gate\McpTenantContext::getActiveTenant();
            return response()->json([
                'status' => 'ok',
                'service' => 'QuickFlow MCP Server',
                'protocolVersion' => '2024-11-05',
                'tenant' => $tenantMeta ? [
                    'name' => $tenantMeta['name'],
                    'slug' => $tenantMeta['slug'],
                    'region' => $tenantMeta['primary_market'],
                ] : null,
            ]);
        }

        $payload = $request->json()->all();

        if (empty($payload) || !is_array($payload)) {
            return response()->json(
                JsonRpcResponse::error(null, JsonRpcResponse::INVALID_REQUEST, 'Invalid or empty JSON payload.'),
                400
            );
        }

        // Support single request or batch requests
        if (array_is_list($payload)) {
            $responses = [];
            foreach ($payload as $singleRequest) {
                if (is_array($singleRequest)) {
                    $res = $this->engine->handle($singleRequest);
                    if ($res !== null) {
                        $responses[] = $res;
                    }
                }
            }
            return response()->json($responses);
        }

        Log::info('MCP INCOMING: ' . json_encode($payload, JSON_UNESCAPED_UNICODE));
        $result = $this->engine->handle($payload);
        Log::info('MCP OUTGOING: ' . json_encode($result, JSON_UNESCAPED_UNICODE));

        if ($result === null) {
            return response()->noContent();
        }

        return response()->json($result);
    }
}
