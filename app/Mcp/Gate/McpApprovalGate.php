<?php

namespace App\Mcp\Gate;

use App\Domains\Property\Models\Property;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class McpApprovalGate
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_TIMEOUT = 'timeout';

    public const QUEUE_KEY = 'mcp_gate_pending_queue';
    public const ACTIVE_GATE_KEY = 'mcp_gate_alive_heartbeat';

    public static bool $enabledInTests = false;
    public static string $mode = 'out_of_band'; // 'in_band' for Video #13, 'out_of_band' for Video #14

    /**
     * Determine if a tool call requires explicit human authorization.
     */
    public function requiresApproval(string $toolName, mixed $tool, array $arguments): bool
    {
        if (self::$mode !== 'out_of_band' && !self::$enabledInTests) {
            return false;
        }

        // Don't block automated test suite runs unless explicitly testing the gate
        if (app()->runningUnitTests() && !self::$enabledInTests) {
            return false;
        }

        // 1. Tool explicitly declares itself destructive
        if (is_object($tool) && method_exists($tool, 'isDestructive') && $tool->isDestructive()) {
            return true;
        }

        // 2. Destructive tool name conventions
        $destructiveKeywords = ['delete', 'destroy', 'drop', 'purge', 'force_delete'];
        $lowerName = strtolower($toolName);
        foreach ($destructiveKeywords as $keyword) {
            if (str_contains($lowerName, $keyword)) {
                return true;
            }
        }

        // 3. Destructive argument flag
        if (!empty($arguments['force_delete'])) {
            return true;
        }

        return false;
    }

    /**
     * Dispatch an authorization request to the Terminal Gate and wait for human decision.
     */
    public function requestAuthorization(string $toolName, array $arguments, int $timeoutSeconds = 180): array
    {
        // Extend PHP execution time limit so gate wait does not trip timeout during video recordings
        @set_time_limit(300);

        $requestId = 'req_' . Str::random(10);
        $ticketCode = 'GATE-' . strtoupper(Str::random(4));

        $targetSummary = $this->resolveTargetSummary($toolName, $arguments);

        $payload = [
            'id' => $requestId,
            'ticket' => $ticketCode,
            'tool_name' => $toolName,
            'arguments' => $arguments,
            'target_summary' => $targetSummary,
            'status' => self::STATUS_PENDING,
            'created_at' => microtime(true),
            'expires_at' => microtime(true) + $timeoutSeconds,
        ];

        // Store active payload
        Cache::put("mcp_gate_payload_{$requestId}", $payload, $timeoutSeconds + 30);
        Cache::put("mcp_gate_ticket_map_{$ticketCode}", $requestId, $timeoutSeconds + 30);

        // Synchronize lock holder with active ticket code for clear multi-agent traceability
        $resourceId = McpResourceLock::extractResourceId($arguments);
        if ($resourceId) {
            Cache::put(McpResourceLock::PREFIX . $resourceId, [
                'resource_id' => $resourceId,
                'holder' => "TICKET-{$ticketCode}",
                'locked_at' => now()->format('H:i:s'),
                'ttl' => $timeoutSeconds,
            ], $timeoutSeconds);
        }

        // Append to active pending queue
        $queue = Cache::get(self::QUEUE_KEY, []);
        $queue[$requestId] = $payload;
        Cache::put(self::QUEUE_KEY, $queue, $timeoutSeconds + 30);

        $startTime = microtime(true);

        try {
            // Polling loop waiting for human decision on terminal
            while ((microtime(true) - $startTime) < $timeoutSeconds) {
                $decision = Cache::get("mcp_gate_decision_{$requestId}");

                if ($decision !== null) {
                    $durationMs = round((microtime(true) - $startTime) * 1000, 2);

                    return [
                        'approved' => $decision === self::STATUS_APPROVED,
                        'status' => $decision,
                        'ticket' => $ticketCode,
                        'target_summary' => $targetSummary,
                        'reason' => $decision === self::STATUS_APPROVED
                            ? "Human Administrator authorized execution via Terminal Gate ({$durationMs}ms)."
                            : "Human Administrator REJECTED this action via Terminal Gate.",
                        'duration_ms' => $durationMs,
                    ];
                }

                usleep(150000); // 150ms check interval
            }
        } finally {
            // Always remove from active pending queue to avoid stale items
            $this->removeFromQueue($requestId);
        }

        // Timeout reached
        Cache::put("mcp_gate_decision_{$requestId}", self::STATUS_TIMEOUT, 60);
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'approved' => false,
            'status' => self::STATUS_TIMEOUT,
            'ticket' => $ticketCode,
            'target_summary' => $targetSummary,
            'reason' => "Human Approval TIMEOUT: No response received from terminal gatekeeper within {$timeoutSeconds}s.",
            'duration_ms' => $durationMs,
        ];
    }

    public function approve(string $requestIdOrTicket): bool
    {
        $requestId = $this->resolveRequestId($requestIdOrTicket);
        if (!$requestId) {
            return false;
        }

        Cache::put("mcp_gate_decision_{$requestId}", self::STATUS_APPROVED, 120);
        $this->removeFromQueue($requestId);
        return true;
    }

    public function reject(string $requestIdOrTicket, string $reason = ''): bool
    {
        $requestId = $this->resolveRequestId($requestIdOrTicket);
        if (!$requestId) {
            return false;
        }

        Cache::put("mcp_gate_decision_{$requestId}", self::STATUS_REJECTED, 120);
        if ($reason) {
            Cache::put("mcp_gate_reject_reason_{$requestId}", $reason, 120);
        }
        $this->removeFromQueue($requestId);
        return true;
    }

    public function getPendingQueue(): array
    {
        $queue = Cache::get(self::QUEUE_KEY, []);
        $now = microtime(true);
        $cleaned = [];

        foreach ($queue as $id => $item) {
            if (isset($item['expires_at']) && $item['expires_at'] > $now) {
                $cleaned[$id] = $item;
            }
        }

        if (count($cleaned) !== count($queue)) {
            Cache::put(self::QUEUE_KEY, $cleaned, 120);
        }

        return $cleaned;
    }

    public function heartBeat(): void
    {
        Cache::put(self::ACTIVE_GATE_KEY, time(), 10);
    }

    public function isGateOnline(): bool
    {
        $lastBeat = Cache::get(self::ACTIVE_GATE_KEY);
        return $lastBeat && (time() - $lastBeat) <= 5;
    }

    protected function resolveRequestId(string $input): ?string
    {
        if (str_starts_with($input, 'req_')) {
            return $input;
        }

        return Cache::get("mcp_gate_ticket_map_{$input}");
    }

    public function removeFromQueue(string $requestId): void
    {
        $queue = Cache::get(self::QUEUE_KEY, []);
        unset($queue[$requestId]);
        Cache::put(self::QUEUE_KEY, $queue, 120);
    }

    protected function resolveTargetSummary(string $toolName, array $arguments): string
    {
        $id = $arguments['property_id'] ?? $arguments['uuid'] ?? $arguments['id'] ?? null;
        if ($id) {
            try {
                $property = Property::where('uuid', $id)->first();
                if ($property) {
                    return "Property '{$property->name}' (ID: {$property->uuid}, Current DB Status: {$property->status})";
                }
            } catch (\Throwable) {
                // Ignore DB lookup issues
            }
            return "Target Resource ID: {$id}";
        }

        $argsJson = json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return "Arguments: {$argsJson}";
    }
}
