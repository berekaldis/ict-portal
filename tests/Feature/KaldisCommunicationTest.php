<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class KaldisCommunicationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'superadmin')->first() ?? User::firstOrCreate(
            ['username' => 'comm_test_admin'],
            [
                'name' => 'Communication Test Admin',
                'email' => 'comm_test_admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin->assignRole($superAdminRole);

        // Ensure permission exists
        Permission::firstOrCreate(['name' => 'communication.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'communication.manage', 'guard_name' => 'web']);
        $this->admin->givePermissionTo(['communication.view', 'communication.manage']);
    }

    public function test_kaldis_communication_index_accessible()
    {
        $response = $this->actingAs($this->admin)->get(route('kaldis-communication.index'));
        $response->assertStatus(200);
    }

    public function test_can_update_communication_config()
    {
        $payload = [
            'bot_token' => '8989019561:AAGZey9AIifl27hM2swv_FhpG31wh5ogPWQ',
            'region_1_chat_id' => '-1003724420241',
            'region_2_chat_id' => '0',
            'operations_director_user_id' => '12345678',
        ];

        $response = $this->actingAs($this->admin)->post(route('kaldis-communication.update-config'), $payload);
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_can_store_and_update_roster_user()
    {
        $testTelegramId = 987654321;

        // Store
        $response = $this->actingAs($this->admin)->post(route('kaldis-communication.store-user'), [
            'telegram_user_id' => $testTelegramId,
            'display_name' => 'Test Branch Manager',
            'role' => 'branch_manager',
            'region' => 'Region 1',
            'branch_name' => 'Bole Mega Cafe',
            'department' => 'Operations',
        ]);
        $response->assertRedirect();

        // Update
        $response = $this->actingAs($this->admin)->put(route('kaldis-communication.update-user'), [
            'telegram_user_id' => $testTelegramId,
            'display_name' => 'Test Senior Branch Manager',
            'role' => 'branch_manager',
            'region' => 'Region 1',
            'branch_name' => 'Bole Mega Cafe',
            'department' => 'Operations',
        ]);
        $response->assertRedirect();

        // Delete
        $response = $this->actingAs($this->admin)->delete(route('kaldis-communication.delete-user', $testTelegramId));
        $response->assertRedirect();
    }

    public function test_can_store_and_delete_topic_binding()
    {
        $response = $this->actingAs($this->admin)->post(route('kaldis-communication.store-binding'), [
            'group_key' => 'Region 1',
            'thread_id' => 99999,
            'topic_name' => 'Test Automated Audit Topic',
            'department' => 'IT',
        ]);
        $response->assertRedirect();

        // Delete
        $response = $this->actingAs($this->admin)->delete(route('kaldis-communication.delete-binding'), [
            'group_key' => 'Region 1',
            'thread_id' => 99999,
        ]);
        $response->assertRedirect();
    }

    public function test_telegram_webhook_endpoint_responds_ok()
    {
        $response = $this->postJson('/api/telegram/kaldis-communication', [
            'update_id' => 123456789,
            'message' => [
                'message_id' => 1,
                'chat' => [
                    'id' => -1003724420241,
                    'title' => 'Test Kaldis Group',
                    'type' => 'supergroup',
                ],
                'from' => [
                    'id' => 555666777,
                    'is_bot' => false,
                    'first_name' => 'Abebe',
                    'last_name' => 'Bikila',
                ],
                'text' => '/help',
                'date' => time(),
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }
}
