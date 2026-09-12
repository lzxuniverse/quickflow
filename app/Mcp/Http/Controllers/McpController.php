<?php

namespace App\Mcp\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mcp\Server\JsonRpcResponse;
use App\Mcp\Server\McpServerEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class McpController extends Controller
{
    protected McpServerEngine $engine;

    public function __construct(McpServerEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Handle incoming HTTP JSON-RPC MCP requests.
     */
    public function handle(Request $request): JsonResponse|Response
    {
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

        $result = $this->engine->handle($payload);

        if ($result === null) {
            return response()->noContent();
        }

        return response()->json($result);
    }
}
