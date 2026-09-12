<?php

namespace App\Mcp\Server;

use App\Mcp\Contracts\ToolInterface;

class ToolRegistry
{
    /**
     * @var array<string, ToolInterface>
     */
    protected array $tools = [];

    public function register(ToolInterface $tool): self
    {
        $this->tools[$tool->getName()] = $tool;
        return $this;
    }

    /**
     * @return array<string, ToolInterface>
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    public function getTool(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    public function hasTool(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Returns tools formatted according to the MCP tools/list schema.
     */
    public function toMcpToolList(): array
    {
        $list = [];

        foreach ($this->tools as $tool) {
            $list[] = [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'inputSchema' => $tool->getInputSchema(),
            ];
        }

        return $list;
    }
}
