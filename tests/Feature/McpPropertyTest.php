<?php

namespace Tests\Feature;

use App\Domains\Property\Models\Property;
use App\Models\McpToolCall;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpPropertyTest extends TestCase
{
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        // Use an existing tenant or create one
        $this->tenant = Tenant::first() ?? Tenant::create([
            'name' => 'Test Hospitality Group',
            'status' => 'active',
        ]);
    }

    public function test_mcp_initialize_via_api(): void
    {
        $response = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [],
                'clientInfo' => ['name' => 'test-client', 'version' => '1.0'],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('jsonrpc', '2.0');
        $response->assertJsonPath('id', 1);
        $response->assertJsonPath('result.protocolVersion', '2024-11-05');
        $response->assertJsonPath('result.serverInfo.name', 'quickflow-property-mcp');
    }

    public function test_mcp_direct_path_without_api_prefix(): void
    {
        $response = $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 99,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('result.tools'));
    }

    public function test_mcp_tools_list_returns_all_property_tools(): void
    {
        $response = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
        $tools = $response->json('result.tools');

        $this->assertIsArray($tools);
        $toolNames = array_column($tools, 'name');

        $this->assertContains('properties_list', $toolNames);
        $this->assertContains('properties_get', $toolNames);
        $this->assertContains('properties_create', $toolNames);
        $this->assertContains('properties_update', $toolNames);
        $this->assertContains('properties_delete', $toolNames);
        $this->assertContains('properties_stats', $toolNames);
    }

    public function test_mcp_properties_list_and_stats(): void
    {
        // Test stats
        $response = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'properties_stats',
                'arguments' => [
                    'tenant_id' => $this->tenant->uuid,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.isError', false);
        $content = json_decode($response->json('result.content.0.text'), true);
        $this->assertArrayHasKey('total_properties', $content);

        // Test list
        $responseList = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => [
                'name' => 'properties_list',
                'arguments' => [
                    'tenant_id' => $this->tenant->uuid,
                    'per_page' => 5,
                ],
            ],
        ]);

        $responseList->assertStatus(200);
        $listData = json_decode($responseList->json('result.content.0.text'), true);
        $this->assertArrayHasKey('properties', $listData);
        $this->assertArrayHasKey('total', $listData);
    }

    public function test_mcp_properties_crud_flow_and_call_logging(): void
    {
        // 1. Create property via MCP
        $createResponse = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 10,
            'method' => 'tools/call',
            'params' => [
                'name' => 'properties_create',
                'arguments' => [
                    'tenant_id' => $this->tenant->uuid,
                    'name' => 'MCP Automated Test Hotel',
                    'currency' => 'USD',
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'address_street' => '123 AI Boulevard',
                    'address_city' => 'Da Nang',
                    'address_country' => 'Vietnam',
                ],
            ],
        ]);

        $createResponse->assertStatus(200);
        $createData = json_decode($createResponse->json('result.content.0.text'), true);
        $this->assertTrue($createData['success']);
        $propertyUuid = $createData['property']['uuid'];

        // 2. Get property via MCP
        $getResponse = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 11,
            'method' => 'tools/call',
            'params' => [
                'name' => 'properties_get',
                'arguments' => [
                    'property_id' => $propertyUuid,
                ],
            ],
        ]);

        $getResponse->assertStatus(200);
        $getData = json_decode($getResponse->json('result.content.0.text'), true);
        $this->assertEquals('MCP Automated Test Hotel', $getData['name']);
        $this->assertEquals('Da Nang', $getData['address']['city']);

        // 3. Update property via MCP
        $updateResponse = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 12,
            'method' => 'tools/call',
            'params' => [
                'name' => 'properties_update',
                'arguments' => [
                    'property_id' => $propertyUuid,
                    'name' => 'MCP Automated Test Hotel (Updated)',
                    'contact_phone' => '+84901234567',
                ],
            ],
        ]);

        $updateResponse->assertStatus(200);
        $updateData = json_decode($updateResponse->json('result.content.0.text'), true);
        $this->assertTrue($updateData['success']);
        $this->assertEquals('MCP Automated Test Hotel (Updated)', $updateData['property']['name']);

        // 4. Soft delete property via MCP
        $deleteResponse = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 13,
            'method' => 'tools/call',
            'params' => [
                'name' => 'properties_delete',
                'arguments' => [
                    'property_id' => $propertyUuid,
                    'force_delete' => false,
                ],
            ],
        ]);

        $deleteResponse->assertStatus(200);
        $deleteData = json_decode($deleteResponse->json('result.content.0.text'), true);
        $this->assertEquals('deactivated', $deleteData['action']);

        // 5. Check property status in DB
        $dbProperty = Property::where('uuid', $propertyUuid)->first();
        $this->assertEquals('inactive', $dbProperty->status);

        // 6. Permanently delete test property
        $permDeleteResponse = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 14,
            'method' => 'tools/call',
            'params' => [
                'name' => 'properties_delete',
                'arguments' => [
                    'property_id' => $propertyUuid,
                    'force_delete' => true,
                ],
            ],
        ]);
        $permDeleteResponse->assertStatus(200);
        $this->assertNull(Property::where('uuid', $propertyUuid)->first());

        // 7. Verify MCP tool call was logged in mcp_tool_calls table
        $callLogged = McpToolCall::where('tool_name', 'properties_create')->latest('created_at')->first();
        $this->assertNotNull($callLogged);
        $this->assertEquals('success', $callLogged->status);
    }
}
