<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class McpTunnelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:tunnel {--url=https://quickflow.test : The local URL to tunnel}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a Cloudflare Tunnel for QuickFlow MCP server with automatic MCP URL detection';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cloudflaredPath = base_path('cloudflared.exe');

        if (!file_exists($cloudflaredPath)) {
            $this->error("cloudflared.exe not found at: {$cloudflaredPath}");
            return Command::FAILURE;
        }

        $localUrl = $this->option('url');
        $hostHeader = parse_url($localUrl, PHP_URL_HOST) ?? 'quickflow.test';

        $this->info("🚀 Initializing Cloudflare Tunnel for {$localUrl} (Host: {$hostHeader})...");

        $command = [
            $cloudflaredPath,
            'tunnel',
            '--url', $localUrl,
            '--http-host-header', $hostHeader,
            '--no-tls-verify',
        ];

        $process = new Process($command);
        $process->setTimeout(null);

        $urlFound = false;

        $process->run(function ($type, $buffer) use (&$urlFound) {
            $this->output->write($buffer);

            // Detect and highlight the MCP URL
            if (!$urlFound && preg_match('/https:\/\/[a-z0-9\-]+\.trycloudflare\.com/', $buffer, $matches)) {
                $urlFound = true;
                $publicUrl = $matches[0];
                $mcpUrl = $publicUrl . '/mcp';

                $this->newLine();
                $this->line('======================================================================');
                $this->info("  🎉 Cloudflare Tunnel is LIVE!");
                $this->info("  👉 MCP URL: \033[1;32m{$mcpUrl}\033[0m");
                $this->line("  👉 Copy this URL directly into Claude Web Connectors");
                $this->line('======================================================================');
                $this->newLine();
            }
        });

        return Command::SUCCESS;
    }
}
