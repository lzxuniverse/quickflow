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
    protected $signature = 'mcp:tunnel 
                            {--provider=cloudflare : The tunnel provider (cloudflare or ngrok)}
                            {--url=https://quickflow.test : The local URL to tunnel}
                            {--domain= : Static domain for ngrok (e.g. your-name.ngrok-free.app)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a Cloudflare or ngrok Tunnel for QuickFlow MCP server with automatic MCP URL detection';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $provider = strtolower($this->option('provider'));
        $localUrl = $this->option('url');
        $hostHeader = parse_url($localUrl, PHP_URL_HOST) ?? 'quickflow.test';

        return match ($provider) {
            'ngrok' => $this->handleNgrokTunnel($localUrl, $hostHeader),
            default => $this->handleCloudflareTunnel($localUrl, $hostHeader),
        };
    }

    /**
     * Handle Cloudflare Tunnel (Quick Tunnel)
     */
    protected function handleCloudflareTunnel(string $localUrl, string $hostHeader): int
    {
        $cloudflaredPath = $this->resolveBinary('cloudflared.exe');

        if (!$cloudflaredPath) {
            $this->error("cloudflared.exe not found in project root or system PATH.");
            return Command::FAILURE;
        }

        $this->info("🚀 Initializing Cloudflare Quick Tunnel for {$localUrl} (Host: {$hostHeader})...");

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

            if (!$urlFound && preg_match('/https:\/\/[a-z0-9\-]+\.trycloudflare\.com/', $buffer, $matches)) {
                $urlFound = true;
                $publicUrl = $matches[0];
                $mcpUrl = $publicUrl . '/mcp';

                $this->newLine();
                $this->line('======================================================================');
                $this->info("  🎉 Cloudflare Tunnel is LIVE!");
                $isReadOnly = \App\Mcp\Gate\McpReadOnlyGuard::isEnabled();
                $policyBadge = $isReadOnly
                    ? "\033[1;33mREAD-ONLY\033[0m (Database mutations blocked)"
                    : "\033[1;31mFULL-ACCESS\033[0m (Mutations permitted)";
                $this->line("  🛡️  Ingress Policy: {$policyBadge}");

                $isRateLimited = \App\Mcp\Gate\McpRateLimiter::isEnabled();
                $capacity = \App\Mcp\Gate\McpRateLimiter::getCapacity();
                $refillRate = \App\Mcp\Gate\McpRateLimiter::getRefillRate();
                $rateBadge = $isRateLimited
                    ? "\033[1;32mACTIVE\033[0m (Token Bucket: {$capacity} burst, {$refillRate} req/s refill)"
                    : "\033[1;31mDISABLED\033[0m";
                $this->line("  ⏱️  Rate Limiter  : {$rateBadge}");
                $this->line("  👉 Note: Ephemeral URL (changes every restart)");
                $this->line("  👉 Ready for: ChatGPT, Claude Web, Cursor, Windsurf, Copilot");
                $this->line('======================================================================');
                $this->newLine();
            }
        });

        return Command::SUCCESS;
    }

    /**
     * Handle ngrok Tunnel (Supports Free Static Domain)
     */
    protected function handleNgrokTunnel(string $localUrl, string $hostHeader): int
    {
        $ngrokPath = $this->resolveBinary('ngrok.exe');

        if (!$ngrokPath) {
            $this->error("ngrok.exe not found in project root or system PATH.");
            $this->line("  👉 Download from: https://ngrok.com/download or run: winget install Ngrok.Ngrok");
            return Command::FAILURE;
        }

        $domain = $this->option('domain') ?: env('NGROK_DOMAIN');
        $authToken = env('NGROK_AUTHTOKEN');

        $this->info("🚀 Initializing ngrok Tunnel for {$localUrl} (Host: {$hostHeader})...");
        if ($domain) {
            $this->info("  📌 Static Domain: {$domain}");
        }

        $command = [
            $ngrokPath,
            'http',
            $localUrl,
            '--host-header=' . $hostHeader,
            '--log=stdout',
        ];

        if ($domain) {
            $formattedDomain = str_starts_with($domain, 'http') ? $domain : 'https://' . $domain;
            $command[] = '--url=' . $formattedDomain;
        }

        if ($authToken) {
            $command[] = '--authtoken=' . $authToken;
        }

        $process = new Process($command);
        $process->setTimeout(null);

        $urlFound = false;

        $process->run(function ($type, $buffer) use (&$urlFound, $domain) {
            $this->output->write($buffer);

            // Extract public url from ngrok stdout logs: url=https://...
            if (!$urlFound && preg_match('/url=(https:\/\/[^\s\033]+)/', $buffer, $matches)) {
                $urlFound = true;
                $publicUrl = rtrim($matches[1], '",');
                $mcpUrl = $publicUrl . '/mcp';

                $this->newLine();
                $this->line('======================================================================');
                $this->info("  🎉 ngrok Static Tunnel is LIVE!");
                $this->info("  👉 MCP URL: \033[1;32m{$mcpUrl}\033[0m");
                if ($domain) {
                    $this->info("  🔒 Zero Reconfiguration: Fixed Static Domain active!");
                } else {
                    $this->line("  💡 Tip: Set NGROK_DOMAIN in .env to use a permanent static domain.");
                }
                $isReadOnly = \App\Mcp\Gate\McpReadOnlyGuard::isEnabled();
                $policyBadge = $isReadOnly
                    ? "\033[1;33mREAD-ONLY\033[0m (Database mutations blocked)"
                    : "\033[1;31mFULL-ACCESS\033[0m (Mutations permitted)";
                $this->line("  🛡️  Ingress Policy: {$policyBadge}");

                $isRateLimited = \App\Mcp\Gate\McpRateLimiter::isEnabled();
                $capacity = \App\Mcp\Gate\McpRateLimiter::getCapacity();
                $refillRate = \App\Mcp\Gate\McpRateLimiter::getRefillRate();
                $rateBadge = $isRateLimited
                    ? "\033[1;32mACTIVE\033[0m (Token Bucket: {$capacity} burst, {$refillRate} req/s refill)"
                    : "\033[1;31mDISABLED\033[0m";
                $this->line("  ⏱️  Rate Limiter  : {$rateBadge}");
                $this->line("  👉 Ready for: ChatGPT, Claude Web, Cursor, Windsurf, Copilot");
                $this->line('======================================================================');
                $this->newLine();
            }
        });

        return Command::SUCCESS;
    }

    /**
     * Resolve binary path from project root or PATH
     */
    protected function resolveBinary(string $binaryName): ?string
    {
        $localPath = base_path($binaryName);
        if (file_exists($localPath)) {
            return $localPath;
        }

        return null;
    }
}
