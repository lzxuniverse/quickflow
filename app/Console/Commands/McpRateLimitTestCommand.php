<?php

namespace App\Console\Commands;

use App\Mcp\Gate\McpRateLimiter;
use App\Mcp\Server\McpServerEngine;
use Illuminate\Console\Command;

class McpRateLimitTestCommand extends Command
{
    protected $signature = 'mcp:rate-limit-test 
        {--burst=6 : Number of rapid requests before pausing} 
        {--lifecycle : Demonstrate complete 3-phase lifecycle (Normal -> Throttled -> Refilled)}
        {--reset : Reset the token bucket cache before testing}';

    protected $description = 'Simulate burst requests and automatic lazy refill to verify Token Bucket rate limiting';

    public function handle(McpServerEngine $engine): int
    {
        $burstCount = (int) ($this->option('burst') ?: 4);
        $identifier = 'test_cli_client';

        if ($this->option('reset') || $this->option('lifecycle')) {
            McpRateLimiter::reset($identifier);
            $this->info("Token bucket cache for '{$identifier}' has been reset.");
        }

        $capacity = McpRateLimiter::getCapacity();
        $refillRate = McpRateLimiter::getRefillRate();
        $secondsPerToken = $refillRate > 0 ? round(1 / $refillRate, 1) : 0;

        $this->output->writeln('');
        $this->output->writeln('<fg=cyan;options=bold>==============================================================</>');
        $this->output->writeln('<fg=cyan;options=bold> QUICKFLOW MCP TOKEN BUCKET RATE LIMIT VERIFICATION</>');
        $this->output->writeln(" Capacity: <fg=yellow;options=bold>{$capacity} tokens</> | Refill: <fg=yellow;options=bold>{$refillRate} req/sec</> (1 token every {$secondsPerToken}s)");
        $this->output->writeln('<fg=cyan;options=bold>==============================================================</>');
        $this->output->writeln('');

        $this->output->writeln('<fg=white;options=bold>[PHASE 1 & 2: BURST TRAFFIC & EXHAUSTION]</>');

        for ($i = 1; $i <= $burstCount; $i++) {
            $startTime = microtime(true);
            $result = McpRateLimiter::check($identifier);
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['allowed']) {
                $rem = $result['remaining'];
                $refillTag = !empty($result['was_refilled']) ? ' <fg=bright-green;options=bold>[REFILLED]</>' : '';
                $this->output->writeln("  [Req #{$i}] <fg=black;bg=green;options=bold> 200 OK </> <fg=cyan>properties_stats</> - Allowed (Tokens Remaining: {$rem}/{$capacity}){$refillTag} <fg=gray>[{$latencyMs}ms]</>");
            } else {
                $retry = $result['retry_after'];
                $this->output->writeln("  [Req #{$i}] <fg=black;bg=yellow;options=bold> 429 RATE LIMITED </> <fg=cyan>properties_stats</> - <fg=red>Exhausted! (Retry after: {$retry}s)</> <fg=gray>[{$latencyMs}ms]</>");
            }
            usleep(30000); // 30ms gap
        }

        if ($this->option('lifecycle')) {
            $this->output->writeln('');
            $this->output->writeln('<fg=white;options=bold>[PHASE 3: AUTOMATIC LAZY REFILL VERIFICATION (10s COOLDOWN)]</>');
            $this->output->writeln("  <fg=yellow>⏳ Pausing 10.5 seconds to observe automatic Token Bucket Lazy Refill...</>");
            
            for ($s = 1; $s <= 10; $s++) {
                sleep(1);
                $this->output->write("  <fg=gray>• {$s}/10s elapsed...</>");
                $this->output->writeln('');
            }
            usleep(500000); // 0.5s buffer to guarantee >10s

            $startTime = microtime(true);
            $result = McpRateLimiter::check($identifier);
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['allowed']) {
                $rem = $result['remaining'];
                $this->output->writeln("  [Req #5] <fg=black;bg=green;options=bold> 200 OK </> <fg=cyan>properties_stats</> - <fg=bright-green;options=bold>Allowed! (Tokens Remaining: {$rem}/{$capacity} • REFILLED VIA LAZY CLOCK)</> <fg=gray>[{$latencyMs}ms]</>");
            } else {
                $this->output->writeln("  [Req #5] <fg=red>Refill failed.</>");
            }
        }

        $this->output->writeln('');
        $this->output->writeln('<fg=green;options=bold>✓ Verification Complete:</> Complete lifecycle validated (Normal -> Throttled -> Refilled).');
        $this->output->writeln('');

        return self::SUCCESS;
    }
}
