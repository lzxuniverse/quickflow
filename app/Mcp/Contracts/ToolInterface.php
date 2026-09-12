<?php

namespace App\Mcp\Contracts;

interface ToolInterface
{
    /**
     * Get the unique name of the MCP tool.
     */
    public function getName(): string;

    /**
     * Get a human-readable description of what the tool does.
     */
    public function getDescription(): string;

    /**
     * Get the JSON Schema defining the input parameters.
     */
    public function getInputSchema(): array;

    /**
     * Execute the tool with the given arguments and return the result array.
     */
    public function execute(array $arguments): array;
}
