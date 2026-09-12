<?php

namespace App\Console\Commands;

use App\Mcp\Server\JsonRpcResponse;
use App\Mcp\Server\McpServerEngine;
use Illuminate\Console\Command;

class McpServeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the QuickFlow Model Context Protocol (MCP) server over standard I/O (stdio)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $engine = new McpServerEngine();

        // Use STDIN / STDOUT for JSON-RPC 2.0 streaming
        $stdin = defined('STDIN') ? STDIN : fopen('php://stdin', 'r');
        $stdout = defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w');

        while (!feof($stdin)) {
            $line = fgets($stdin);
            if ($line === false) {
                break;
            }

            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $request = json_decode($trimmed, true);

            if (!is_array($request)) {
                $response = JsonRpcResponse::error(null, JsonRpcResponse::PARSE_ERROR, 'Parse error: invalid JSON.');
                fwrite($stdout, json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
                fflush($stdout);
                continue;
            }

            $response = $engine->handle($request);

            if ($response !== null) {
                fwrite($stdout, json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
                fflush($stdout);
            }
        }

        return Command::SUCCESS;
    }
}
