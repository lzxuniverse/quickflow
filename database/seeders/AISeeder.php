<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AISeeder extends Seeder
{
    public function run(): void
    {
        echo "5. AISeeder: Configuring Knowledge Documents and SOP Policies...
";

        $tables = ['ai_assistants', 'ai_conversations', 'ai_messages', 'knowledge_bases', 'knowledge_documents', 'knowledge_chunks', 'mcp_servers', 'mcp_tool_calls', 'agent_approvals_queue'];
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        $tenantIds = DB::table('tenants')->pluck('uuid')->toArray();

        foreach ($tenantIds as $tenantId) {
            $kbUuid = (string) Str::uuid();
            DB::table('knowledge_bases')->insert([
                'uuid' => $kbUuid,
                'tenant_id' => $tenantId,
                'name' => 'Global Brand Operations SOP'
            ]);

            // Housekeeping SOP Guide
            DB::table('knowledge_documents')->insert([
                'uuid' => (string) Str::uuid(),
                'knowledge_base_id' => $kbUuid,
                'title' => 'Housekeeping Standard Operating Procedures',
                'content' => "## Room Cleaning Standards

1. Disinfect high-touch surfaces (remotes, handles, switches).
2. Linens must be replaced on check-out, or every 3 days during long stays.
3. Verify minibar status and log consumption into folios.
4. Complete room inspection status logs before marking room clean."
            ]);

            // OTA Onboarding & Onboarding Guide
            DB::table('knowledge_documents')->insert([
                'uuid' => (string) Str::uuid(),
                'knowledge_base_id' => $kbUuid,
                'title' => 'OTA Channel Mapping Guide',
                'content' => "## Channel Sync Standards

1. Confirm room codes map identically in Booking.com and Expedia extranets.
2. Enable XML Push updates for availability changes to avoid double bookings.
3. Base rate adjustments must be verified against tax-inclusive policy settings."
            ]);

            // AI assistant Reception Copilot Agent
            DB::table('ai_assistants')->insert([
                'uuid' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'name' => 'Front Desk Global Copilot',
                'role' => 'ops_copilot',
                'system_prompt' => 'You are an intelligent front desk copilot helping managers with reservations, room status updates, and checking guests in.',
                'model_name' => 'gemini-1.5-flash'
            ]);
        }

        echo "AISeeder complete. Created operational SOP manuals.
";
    }
}
