<?php

namespace App\Console\Commands;

use App\Mcp\Gate\McpApprovalGate;
use App\Models\McpToolCall;
use Illuminate\Console\Command;

class McpGateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:gate 
                            {--poll=200 : Polling interval in milliseconds}
                            {--approve= : Immediately approve a pending ticket or request ID}
                            {--reject= : Immediately reject a pending ticket or request ID}
                            {--lock= : Manually acquire a mutex lock on a resource UUID (Simulate Agent-1)}
                            {--unlock= : Manually release a mutex lock on a resource UUID (SRE Override)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the QuickFlow Human-in-the-Loop MCP Security Gatekeeper';

    /**
     * Execute the console command.
     */
    public function handle(McpApprovalGate $gate): int
    {
        $resLock = new \App\Mcp\Gate\McpResourceLock();

        // 1. One-shot lock/unlock operations
        if ($resourceToLock = $this->option('lock')) {
            $holder = 'AGENT-1-ALPHA';
            if ($resLock->acquire($resourceToLock, $holder, 180)) {
                $this->info("🔒 [MUTEX ACQUIRED] Resource {$resourceToLock} is now LOCKED by [{$holder}] (TTL: 180s).");
                return 0;
            }
            $existing = $resLock->getLock($resourceToLock);
            $this->warn("⚠️  Resource {$resourceToLock} is already locked by [{$existing['holder']}].");
            return 1;
        }

        if ($resourceToUnlock = $this->option('unlock')) {
            $resLock->release($resourceToUnlock);
            $this->info("🔓 [MUTEX RELEASED] Successfully force-released lock on resource {$resourceToUnlock}.");
            return 0;
        }

        // 2. One-shot approval/rejection support
        if ($ticketToApprove = $this->option('approve')) {
            if ($gate->approve($ticketToApprove)) {
                $this->info("✅ Successfully approved ticket: {$ticketToApprove}");
                return 0;
            }
            $this->error("❌ Could not find or approve ticket: {$ticketToApprove}");
            return 1;
        }

        if ($ticketToReject = $this->option('reject')) {
            if ($gate->reject($ticketToReject, 'Explicitly rejected via CLI option.')) {
                $this->info("❌ Successfully rejected ticket: {$ticketToReject}");
                return 0;
            }
            $this->error("❌ Could not find or reject ticket: {$ticketToReject}");
            return 1;
        }

        // 2. Interactive Live Gatekeeper Mode
        $this->output->writeln('');
        $this->output->writeln('<fg=cyan;options=bold>======================================================</>');
        $this->output->writeln('<fg=white;options=bold> QUICKFLOW MCP HUMAN-IN-THE-LOOP APPROVAL GATE </><fg=cyan;options=bold>(v1.0)</>');
        $this->output->writeln('<fg=bright-white> Real-Time Agent Mutation Interceptor & Interactive Authorizer</>');
        $this->output->writeln('<fg=cyan;options=bold>======================================================</>');
        $this->output->writeln('<fg=green;options=bold> ● STATUS: ONLINE </><fg=cyan;options=bold>| Mode: STRICT CONFIRMATION ENFORCED [y/N]</>');
        $this->output->writeln('<fg=bright-white> Listening for tool calls & gate intercept requests... Press Ctrl+C to stop.</>');
        $this->output->writeln('<fg=cyan;options=bold>======================================================</>');
        $this->output->writeln('');

        $pollMs = (int) $this->option('poll');
        $seenUuids = [];
        $handledRequestIds = [];

        // Pre-populate seen calls so we don't spam old logs
        $initialCalls = McpToolCall::orderBy('created_at', 'desc')->take(3)->get();
        foreach ($initialCalls as $call) {
            $seenUuids[$call->uuid] = true;
        }

        while (true) {
            $gate->heartBeat();

            // A. Check for Pending Approval Intercepts (Highest Priority)
            $pendingQueue = $gate->getPendingQueue();

            foreach ($pendingQueue as $reqId => $req) {
                // Skip and purge expired requests
                if (isset($req['expires_at']) && $req['expires_at'] < microtime(true)) {
                    $gate->removeFromQueue($reqId);
                    continue;
                }

                if (!isset($handledRequestIds[$reqId])) {
                    $handledRequestIds[$reqId] = true;
                    $this->handleInteractivePrompt($gate, $reqId, $req);
                }
            }

            // B. Stream Regular Tool Calls Audit Log
            $recentCalls = McpToolCall::orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->reverse();

            foreach ($recentCalls as $call) {
                if (!isset($seenUuids[$call->uuid])) {
                    $this->renderToolCall($call);
                    $seenUuids[$call->uuid] = true;

                    if (count($seenUuids) > 1000) {
                        $seenUuids = array_slice($seenUuids, -500, null, true);
                    }
                }
            }

            usleep($pollMs * 1000);
        }

        return 0;
    }

    protected function handleInteractivePrompt(McpApprovalGate $gate, string $reqId, array $req): void
    {
        // Terminal audible alert
        echo "\x07";

        $timeStr = now()->format('H:i:s');
        $ticket = $req['ticket'] ?? 'UNKNOWN';
        $tool = $req['tool_name'] ?? 'unknown_tool';
        $target = $req['target_summary'] ?? 'Unknown target';
        $argsJson = json_encode($req['arguments'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->output->writeln('');
        $this->output->writeln('<fg=red;options=bold>╔══════════════════════════════════════════════════════╗</>');
        $this->output->writeln('<fg=red;options=bold>║ ⚠️  [GATE INTERCEPT] HUMAN AUTHORIZATION REQUIRED     ║</>');
        $this->output->writeln('<fg=red;options=bold>╚══════════════════════════════════════════════════════╝</>');
        $this->output->writeln("  <fg=yellow;options=bold>• Ticket    :</> <fg=white;options=bold>{$ticket}</>");
        $this->output->writeln("  <fg=yellow;options=bold>• Tool      :</> <fg=cyan;options=bold>{$tool}</>");
        $this->output->writeln("  <fg=yellow;options=bold>• Target    :</> <fg=white>{$target}</>");
        $this->output->writeln("  <fg=yellow;options=bold>• Arguments :</> <fg=bright-white>{$argsJson}</>");
        $this->output->writeln("  <fg=yellow;options=bold>• Time      :</> <fg=bright-white>{$timeStr}</>");
        $this->output->writeln('<fg=red>──────────────────────────────────────────────────────</>');

        $response = $this->ask('<fg=yellow;options=bold>Authorize this destructive action? [y/N]</>');
        $decision = strtolower(trim($response ?? ''));

        if ($decision === 'y' || $decision === 'yes') {
            $gate->approve($reqId);
            $this->output->writeln("<fg=green;options=bold>  ✅ [GATE APPROVED] Execution permit granted for Ticket {$ticket}! Permitting tool...</>");
        } else {
            $gate->reject($reqId, 'Administrator explicitly rejected the action on terminal gate.');
            $this->output->writeln("<fg=red;options=bold>  ❌ [GATE REJECTED] Action blocked. Returned security abort envelope to AI Agent.</>");
            $this->output->writeln("<fg=bright-white>  🛡️  [SAFEGUARD] Target remains UNTOUCHED and preserved in database.</>");
        }

        $this->output->writeln('<fg=red>──────────────────────────────────────────────────────</>');
        $this->output->writeln('');
    }

    protected function renderToolCall(McpToolCall $call): void
    {
        $timeStr = $call->created_at ? $call->created_at->format('H:i:s') : now()->format('H:i:s');
        $statusTag = $call->status === 'success'
            ? '<fg=black;bg=green;options=bold> SUCCESS </>'
            : '<fg=white;bg=red;options=bold> BLOCKED </>';

        $toolTag = "<fg=cyan;options=bold>{$call->tool_name}</>";

        $durationStr = '';
        $responseDecoded = json_decode($call->response, true);
        if (is_array($responseDecoded) && isset($responseDecoded['_meta']['duration_ms'])) {
            $ms = $responseDecoded['_meta']['duration_ms'];
            $durationStr = "<fg=yellow>[{$ms}ms]</>";
        }

        // Gate indicator
        $gateTag = '';
        if (is_array($responseDecoded) && isset($responseDecoded['_gate_authorization'])) {
            $gateTag = ' <fg=black;bg=cyan;options=bold> GATE-AUTHORIZED </>';
        } elseif (is_array($responseDecoded) && isset($responseDecoded['gate_status'])) {
            $gateTag = ' <fg=white;bg=magenta;options=bold> GATE-BLOCKED </>';
        }

        $this->output->writeln("<fg=green;options=bold>[LIVE]</>  <fg=white;options=bold>[{$timeStr}]</>  {$statusTag}{$gateTag}  {$toolTag}  {$durationStr}");

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

        if ($call->status === 'error') {
            $err = is_array($responseDecoded) ? ($responseDecoded['error'] ?? $call->response) : $call->response;
            $this->output->writeln("  <fg=red;options=bold>↳ Gate:</> <fg=red>{$err}</>");
        } else {
            $msg = is_array($responseDecoded) ? ($responseDecoded['message'] ?? 'OK') : 'OK';
            $this->output->writeln("  <fg=cyan;options=bold>↳ Resp:</> <fg=#E2E8F0;options=bold>{$msg}</>");
        }

        $this->output->writeln('');
    }
}
