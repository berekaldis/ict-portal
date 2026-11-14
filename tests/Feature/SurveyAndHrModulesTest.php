<?php

namespace Tests\Feature;

use App\Models\HrInternshipRequest;
use App\Models\HrQualification;
use App\Models\HrRecruitment;
use App\Models\HrSetting;
use App\Models\HrSystemConfig;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyAndHrModulesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \Database\Seeders\SurveyAndHrSeeder::class;
    }

    public function test_public_customer_survey_loads_and_submits(): void
    {
        // 1. View public customer questionnaire
        $response = $this->get('/survey?branch=Sarbet&table=04');
        $response->assertStatus(200);

        // 2. Submit survey response
        $submitData = [
            'branch' => 'Sarbet',
            'table_number' => '04',
            'q1_frequency' => 'በሳምንት 1–2 ጊዜ',
            'q2_reason' => 'ቡና/መጠጥ',
            'q3_spend' => '150–250 ብር',
            'q4_orders' => ['ትኩስ ቡና', 'ቁርስ'],
            'q8_price_rating' => 'ተመጣጣኝ',
            'q14_waiter_rating' => 'በጣም ጥሩ',
            'q16_interior_rating' => 'ጥሩ',
            'q19_service_suggestions' => 'የጠዋት ፈጣን አገልግሎት ቢቀጥል ይመረጣል።',
            'q20_visit_motivation' => 'ጥሩ ቡና እና ወዳጃዊ ድባብ',
        ];

        $postResponse = $this->postJson('/survey/submit', $submitData);
        $postResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('surveys', [
            'branch' => 'Sarbet',
            'table_number' => '04',
            'q14_waiter_rating' => 'በጣም ጥሩ',
        ]);
    }

    public function test_it_survey_admin_analytics_and_export(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Admin analytics page
        $this->actingAs($superadmin)->get('/it-surveys')->assertStatus(200);

        // 2. Hardware Inventory
        $this->actingAs($superadmin)->get('/it-surveys/hardware')->assertStatus(200);

        // 3. CSV Export
        $exportResponse = $this->actingAs($superadmin)->get('/it-surveys/export/reports');
        $exportResponse->assertStatus(200);
    }

    public function test_hr_dashboard_and_settings(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // HR Dashboard
        $this->actingAs($superadmin)->get('/hr')->assertStatus(200);

        // HR Settings
        $this->actingAs($superadmin)->get('/hr/settings')->assertStatus(200);

        // Add setting
        $addSetting = $this->actingAs($superadmin)->post('/hr/settings', [
            'type' => 'branch',
            'value' => 'Test Branch Kazanchis',
            'chat_id' => '-1001234567890',
        ]);
        $addSetting->assertRedirect();
        $this->assertDatabaseHas('hr_settings', ['value' => 'Test Branch Kazanchis']);

        // Update Bot Token
        $botTokenResponse = $this->actingAs($superadmin)->post('/hr/settings/bot-token', [
            'bot_token' => '123456789:ABCDEF_TEST_TOKEN',
        ]);
        $botTokenResponse->assertRedirect();
        $this->assertEquals('123456789:ABCDEF_TEST_TOKEN', HrSystemConfig::get('bot_token'));
    }

    public function test_hr_internship_lifecycle_and_letter(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();

        // 1. Create Internship Request
        $storeResponse = $this->actingAs($superadmin)->post('/hr/internships', [
            'name' => 'Abebe Kebede',
            'gender' => 'አቶ',
            'to_branch' => 'Bole',
            'work_branch' => 'Sarbet',
            'job' => 'Barista',
            'start_date' => '2026-12-01',
        ]);
        $storeResponse->assertRedirect();

        $internship = HrInternshipRequest::where('name', 'Abebe Kebede')->first();
        $this->assertNotNull($internship);
        $this->assertStringStartsWith('IRF-', $internship->request_id);

        // 2. View Letter
        $letterResponse = $this->actingAs($superadmin)->get("/hr/internships/{$internship->id}/letter");
        $letterResponse->assertStatus(200)
            ->assertJson([
                'ref_no' => $internship->request_id,
                'name' => 'Abebe Kebede',
            ]);

        // 3. Update Status
        $statusResponse = $this->actingAs($superadmin)->put("/hr/internships/{$internship->id}/status", [
            'reference_status' => 'Approved',
        ]);
        $statusResponse->assertRedirect();
        $this->assertEquals('Approved', $internship->fresh()->reference_status);
    }

    public function test_hr_qualification_lifecycle_and_letter(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $storeResponse = $this->actingAs($superadmin)->post('/hr/qualifications', [
            'name' => 'Tigist Haile',
            'gender' => 'ወ/ሪት',
            'branch' => 'Bole',
            'job' => 'Cashier',
            'probation_start' => '2026-08-01',
            'manager_name' => 'Mulugeta Tesfaye',
            'manager_rank' => 'Branch Manager',
        ]);
        $storeResponse->assertRedirect();

        $qual = HrQualification::where('name', 'Tigist Haile')->first();
        $this->assertNotNull($qual);
        $this->assertStringStartsWith('QF-', $qual->request_id);

        // View Letter
        $letterResponse = $this->actingAs($superadmin)->get("/hr/qualifications/{$qual->id}/letter");
        $letterResponse->assertStatus(200)
            ->assertJson([
                'ref_no' => $qual->request_id,
                'manager_name' => 'Mulugeta Tesfaye',
            ]);
    }

    public function test_hr_recruitment_lifecycle_and_letter(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $storeResponse = $this->actingAs($superadmin)->post('/hr/recruitments', [
            'name' => 'Dawit Yohannes',
            'gender' => 'አቶ',
            'branch' => 'Kazanchis',
            'requested_job' => 'Cook',
            'assigned_job' => 'Senior Cook',
            'start_date' => '2026-11-15',
        ]);
        $storeResponse->assertRedirect();

        $rec = HrRecruitment::where('name', 'Dawit Yohannes')->first();
        $this->assertNotNull($rec);
        $this->assertStringStartsWith('RF-', $rec->request_id);

        // View Letter
        $letterResponse = $this->actingAs($superadmin)->get("/hr/recruitments/{$rec->id}/letter");
        $letterResponse->assertStatus(200)
            ->assertJson([
                'ref_no' => $rec->request_id,
                'assigned_job' => 'Senior Cook',
            ]);
    }
}
