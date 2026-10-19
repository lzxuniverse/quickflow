<?php

namespace App\Console\Commands;

use App\Models\McpToolCall;
use Illuminate\Console\Command;

class McpTailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:tail 
                            {--limit=5 : Number of initial recent records to show}
                            {--poll=500 : Polling interval in milliseconds}
                            {--reset : Reset token bucket cache to full capacity on startup}
                            {--once : Display recent logs and exit without live streaming}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stream real-time MCP tool call audit logs with live latency and status indicators';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('reset')) {
            \App\Mcp\Gate\McpRateLimiter::reset();
        }

        $initStatus = \App\Mcp\Gate\McpRateLimiter::getStatus('mcp_tunnel_ingress');
        $initTokens = (int) floor($initStatus['tokens']);
        $initCap = $initStatus['capacity'];

        $this->output->writeln('');
        $this->output->writeln('<fg=cyan;options=bold>======================================================</>');
        $this->output->writeln('<fg=white;options=bold> QUICKFLOW MCP OBSERVABILITY STREAM </><fg=cyan;options=bold>(Live Audit)</>');
        $this->output->writeln(" <fg=bright-white>Listening for tool calls... [Bucket: </><fg=yellow;options=bold>{$initTokens}/{$initCap} tokens</><fg=bright-white>]</>");
        $this->output->writeln('<fg=cyan;options=bold>======================================================</>');
        $this->output->writeln('');

        $limit = (int) $this->option('limit');
        $pollMs = (int) $this->option('poll');
        $seenUuids = [];

        // 1. Pre-load initial records
        $initialCalls = McpToolCall::orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->reverse();

        foreach ($initialCalls as $call) {
            $this->renderCall($call, isHistorical: true);
            $seenUuids[$call->uuid] = true;
        }

        if ($this->option('once')) {
            return 0;
        }

        if ($initialCalls->isNotEmpty()) {
            $this->output->writeln('<fg=cyan;options=bold>────────────── [LIVE STREAMING BEGINS] ──────────────</>');
        }

        // 2. Poll loop for live events
        $wasExhausted = false;
        $lastTickerSecond = -1;

        while (true) {
            $recentCalls = McpToolCall::orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->reverse();

            foreach ($recentCalls as $call) {
                if (!isset($seenUuids[$call->uuid])) {
                    $this->renderCall($call, isHistorical: false);
                    $seenUuids[$call->uuid] = true;

                    // Keep memory bounded
                    if (count($seenUuids) > 1000) {
                        $seenUuids = array_slice($seenUuids, -500, null, true);
                    }
                }
            }

            // 30s Cooldown Penalty Tracker for Audience Transparency
            $status = \App\Mcp\Gate\McpRateLimiter::getStatus('mcp_tunnel_ingress');
            if ($status['is_throttled']) {
                $wasExhausted = true;
                $remSec = $status['seconds_remaining'];

                // Print clean milestone countdown every 5 seconds (25s, 20s, 15s, 10s, 5s)
                if ($remSec > 0 && $remSec % 5 === 0 && $remSec !== $lastTickerSecond) {
                    $lastTickerSecond = $remSec;
                    $this->output->writeln("  <fg=yellow;options=bold>⏳ COOLDOWN IN PROGRESS:</> <fg=bright-yellow;options=bold>{$remSec}s remaining</> until server unlocks for next prompt...");
                }
            } elseif ($wasExhausted) {
                // Celebrate full unlock
                $wasExhausted = false;
                $lastTickerSecond = -1;
                $cap = $status['capacity'];
                $this->output->writeln('');
                $this->output->writeln("  <fg=black;bg=green;options=bold> ✨ 30s COOLDOWN COMPLETE </> <fg=bright-green;options=bold>Token Bucket restored ({$cap}/{$cap})! Ready for next prompt!</>");
                $this->output->writeln('');
            }

            usleep($pollMs * 1000);
        }

        return 0;
    }

    protected function renderCall(McpToolCall $call, bool $isHistorical = false): void
    {
        $timeStr = $call->created_at ? $call->created_at->format('H:i:s') : now()->format('H:i:s');
        $responseDecoded = json_decode($call->response, true);

        $isReadOnly = str_contains($call->response ?? '', 'READ-ONLY') || str_contains($call->response ?? '', 'DATABASE ACCESS DENIED');
        $isRateLimited = str_contains($call->response ?? '', 'RATE LIMIT EXCEEDED') || str_contains($call->response ?? '', 'TOKEN_BUCKET');
        $isTenantUnauthorized = is_array($responseDecoded) && ($responseDecoded['guard'] ?? '') === 'TENANT_CAPABILITY_INGRESS';

        $statusTag = match (true) {
            $isTenantUnauthorized => '<fg=white;bg=red;options=bold> 401 UNAUTHORIZED </>',
            $isRateLimited => '<fg=black;bg=yellow;options=bold> RATE LIMITED (429) </>',
            $isReadOnly => '<fg=white;bg=red;options=bold> READ-ONLY BLOCKED </>',
            $call->tool_name === 'auth:shield' => '<fg=white;bg=red;options=bold> BLOCKED </>',
            $call->status === 'error' => '<fg=white;bg=red;options=bold> ERROR </>',
            is_array($responseDecoded) && ($responseDecoded['status'] ?? '') === 'CONFIRMATION_REQUIRED' => '<fg=black;bg=yellow;options=bold> CHALLENGE </>',
            default => '<fg=black;bg=green;options=bold> SUCCESS </>',
        };

        $toolTag = $call->tool_name === 'auth:shield'
            ? '<fg=bright-yellow;options=bold>auth:shield</>'
            : "<fg=cyan;options=bold>{$call->tool_name}</>";

        // Extract metadata if present
        $durationStr = '';
        if (is_array($responseDecoded) && isset($responseDecoded['_meta']['duration_ms'])) {
            $ms = $responseDecoded['_meta']['duration_ms'];
            $durationStr = "<fg=yellow>[{$ms}ms]</>";
        }

        $tenantBadge = '';
        if (is_array($responseDecoded)) {
            $meta = $responseDecoded['_meta'] ?? [];
            if (!empty($meta['tenant']['slug'])) {
                $tenantSlug = $meta['tenant']['slug'];
                $tenantBadge = match ($tenantSlug) {
                    'ALPHA' => '<fg=black;bg=cyan;options=bold> TENANT: ALPHA (FR) </>',
                    'BETA' => '<fg=black;bg=magenta;options=bold> TENANT: BETA (DE) </>',
                    default => "<fg=black;bg=blue;options=bold> TENANT: {$tenantSlug} </>",
                };
            } elseif ($isTenantUnauthorized) {
                $tenantBadge = '<fg=white;bg=red;options=bold> UNTRUSTED TENANT </>';
            }
        }

        $tokenBadge = '';
        if (is_array($responseDecoded)) {
            $meta = $responseDecoded['_meta'] ?? [];
            if ($isRateLimited) {
                $retry = $meta['retry_after_seconds'] ?? 60;
                $cap = $meta['capacity'] ?? \App\Mcp\Gate\McpRateLimiter::getCapacity();
                $tokenBadge = "<fg=bright-red>[Tokens: 0/{$cap} • EXHAUSTED (Retry: {$retry}s)]</>";
            } elseif (isset($meta['token_bucket'])) {
                $tb = $meta['token_bucket'];
                $rem = $tb['remaining'] ?? 0;
                $cap = $tb['capacity'] ?? 5;
                if (!empty($tb['was_refilled'])) {
                    $tokenBadge = "<fg=bright-green>[Tokens: {$rem}/{$cap} • REFILLED (+{$tb['refilled_tokens']})]</>";
                } else {
                    $tokenBadge = "<fg=gray>[Tokens: {$rem}/{$cap}]</>";
                }
            }
        }

        $histBadge = $isHistorical ? '<fg=cyan;options=bold>[HISTORY]</> ' : '<fg=green;options=bold>[LIVE]</> ';

        $tenantSpacing = $tenantBadge ? " {$tenantBadge} " : " ";
        $this->output->writeln("{$histBadge}<fg=white;options=bold>[{$timeStr}]</>  {$statusTag}{$tenantSpacing}{$toolTag}  {$durationStr}  {$tokenBadge}");

        // Prominent Warning when Bucket is Fully Depleted
        if (is_array($responseDecoded) && isset($responseDecoded['_meta']['token_bucket'])) {
            $tb = $responseDecoded['_meta']['token_bucket'];
            if (($tb['remaining'] ?? 1) === 0 && !$isRateLimited) {
                $cap = $tb['capacity'] ?? 3;
                $this->output->writeln("  <fg=black;bg=yellow;options=bold> ⚠️  QUOTA WARNING </> <fg=bright-yellow;options=bold>Token Bucket completely depleted (0/{$cap})! Next request will trigger HTTP 429.</>");
            }
        }

        // Arguments snippet
        if (empty($call->arguments)) {
            $this->output->writeln("  <fg=cyan;options=bold>↳ Args:</> <fg=#E2E8F0>[]</>");
        } else {
            $argsJson = json_encode($call->arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (count($call->arguments) === 1 && strlen($argsJson) <= 30) {
                $this->output->writeln("  <fg=cyan;options=bold>↳ Args:</> <fg=#E2E8F0>{$argsJson}</>");
            } else {
                $this->output->writeln("  <fg=cyan;options=bold>↳ Args:</>");
                foreach ($call->arguments as $key => $val) {
                    $valStr = is_bool($val) ? ($val ? 'true' : 'false') : (is_scalar($val) ? (string) $val : json_encode($val));
                    $this->output->writeln("    <fg=#22D3EE>•</> <fg=#94A3B8>{$key}:</> <fg=#E2E8F0;options=bold>{$valStr}</>");
                }
            }
        }

        // Response or Error snippet
        if ($call->status === 'error') {
            $err = is_array($responseDecoded) ? ($responseDecoded['error'] ?? $call->response) : $call->response;
            $this->output->writeln("  <fg=red;options=bold>↳ Err :</> <fg=red>{$err}</>");
        } elseif (is_array($responseDecoded) && ($responseDecoded['status'] ?? '') === 'CONFIRMATION_REQUIRED') {
            $ticket = $responseDecoded['confirmation_ticket'] ?? 'NONE';
            $this->output->writeln("  <fg=yellow;options=bold>↳ Gate:</> <fg=yellow>CONFIRMATION_REQUIRED</> <fg=cyan;options=bold>[Ticket: {$ticket}]</>");
        } else {
            $msg = is_array($responseDecoded) ? ($responseDecoded['message'] ?? 'OK') : 'OK';
            $this->output->writeln("  <fg=cyan;options=bold>↳ Resp:</> <fg=#E2E8F0;options=bold>{$msg}</>");
        }

        $this->output->writeln('');
    }
}
