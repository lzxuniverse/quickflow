<?php

namespace App\Mcp\Gate;

class McpReadOnlyGuard
{
    /**
     * Explicit whitelist of safe query tools that never mutate database state.
     */
    protected const SAFE_READ_TOOLS = [
        'properties_stats',
        'properties_list',
        'properties_get',
    ];

    /**
     * Keywords that signal destructive or mutating actions.
     */
    protected const MUTATION_KEYWORDS = [
        'create',
        'update',
        'delete',
        'destroy',
        'drop',
        'patch',
        'modify',
        'insert',
        'purge',
        'remove',
    ];

    /**
     * Determine if Read-Only Ingress enforcement is active.
     */
    public static function isEnabled(): bool
    {
        return (bool) config('mcp.read_only', env('MCP_READ_ONLY', true));
    }

    /**
     * Determine if a tool performs a state mutation (INSERT / UPDATE / DELETE).
     */
    public static function isMutation(string $toolName, mixed $tool = null): bool
    {
        $lowerName = strtolower(trim($toolName));

        // 1. Check against explicit safe read whitelist
        if (in_array($lowerName, self::SAFE_READ_TOOLS, true)) {
            return false;
        }

        // 2. Check for mutation keywords
        foreach (self::MUTATION_KEYWORDS as $keyword) {
            if (str_contains($lowerName, $keyword)) {
                return true;
            }
        }

        // 3. Check tool object capability if declared
        if (is_object($tool) && method_exists($tool, 'isMutation')) {
            return (bool) $tool->isMutation();
        }

        if (is_object($tool) && method_exists($tool, 'isDestructive')) {
            return (bool) $tool->isDestructive();
        }

        return false;
    }

    /**
     * Generate clear, human-readable rejection message for AI client.
     */
    public static function getRejectionMessage(string $toolName): string
    {
        return "DATABASE ACCESS DENIED: The QuickFlow MCP Server is currently running in READ-ONLY mode on this public tunnel ingress. Tool '{$toolName}' performs destructive database mutations and is strictly blocked by the Principle of Least Privilege to protect your local database.";
    }
}
