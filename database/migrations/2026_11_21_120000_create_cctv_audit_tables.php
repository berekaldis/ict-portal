<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. CCTV Branch Register (5 Branches)
        if (!Schema::hasTable('cctv_branch_registers')) {
            Schema::create('cctv_branch_registers', function (Blueprint $table) {
                $table->id();
                $table->integer('branch_no')->default(1);
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('branch_name', 150);
                $table->string('nvr_model_ip', 150)->default('Dahua NVR / 192.168.1.100');
                $table->integer('camera_count')->default(16);
                $table->string('hdd_size_days', 100)->default('2x 4TB WD Purple / 30 days');
                $table->string('firmware', 100)->default('V4.002.0000000.1.R');
                $table->date('last_check')->nullable();
                $table->string('status', 50)->default('operational'); // operational, warning, critical, maintenance
                $table->text('issues')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. CCTV Incident Logs
        if (!Schema::hasTable('cctv_incident_logs')) {
            Schema::create('cctv_incident_logs', function (Blueprint $table) {
                $table->id();
                $table->string('incident_number', 50)->unique();
                $table->date('incident_date');
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('branch_name', 150);
                $table->string('device_name', 150);
                $table->string('issue_type', 100); // Offline, No recording, HDD error, Time wrong, Blurry/Angle, Other
                $table->enum('severity', ['critical', 'major', 'minor'])->default('minor');
                $table->text('impact')->nullable();
                $table->text('first_check_done')->nullable();
                $table->string('reported_by', 150);
                $table->string('reported_at', 50)->nullable();
                $table->string('fixed_at', 50)->nullable();
                $table->enum('status', ['open', 'investigating', 'in_progress', 'resolved'])->default('open');
                $table->text('action_taken')->nullable();
                $table->timestamp('telegram_announced_at')->nullable();
                $table->timestamps();
            });
        }

        // 3. Seed the full CCTV Checklist items into it_survey_categories & checklist items
        $this->seedCctvChecklistCategories();
    }

    private function seedCctvChecklistCategories(): void
    {
        $cctvCategories = [
            [
                'name' => 'CCTV – 1. One-time Setup & Security Verification',
                'icon' => 'fas fa-shield-alt',
                'type' => 'hardware',
                'has_photo' => true,
                'description' => 'Security hardening, accounts, VLAN, NTP, and HDD setup per Dahua standards.',
                'display_order' => 50,
                'items' => [
                    'Default passwords changed on NVR and all cameras (unique strong password per branch)',
                    'Admin accounts limited; one account per person; ex-staff removed',
                    'P2P/Cloud and UPnP off unless needed; remote access via VPN only (no port-forwarding)',
                    'CCTV on separate VLAN/network from office PCs and guest Wi-Fi',
                    'NTP set, time zone GMT+3 (Addis Ababa), correct date/time',
                    'Surveillance-grade HDDs installed (WD Purple / Seagate SkyHawk)',
                    'Recording mode set (continuous or motion) and retention target set (e.g. 30 days)',
                    'Auto-maintenance/reboot time set (off-hours)',
                    'UPS connected to NVR, PoE switch and router',
                    'Branch register filled: NVR model/IP, camera count, HDD size, firmware, serial numbers',
                    'NVR config backup exported and stored',
                ],
            ],
            [
                'name' => 'CCTV – 2. Daily Remote Health Check (5 min - Helpdesk)',
                'icon' => 'fas fa-desktop',
                'type' => 'hardware',
                'has_photo' => false,
                'description' => 'Remote daily verification via SmartPSS / DMSS / NVR Web.',
                'display_order' => 51,
                'items' => [
                    'All cameras online (SmartPSS / DMSS / NVR web)',
                    'NVR recording on all channels',
                    'NVR date/time correct',
                    'No HDD error or disk-full alert',
                ],
            ],
            [
                'name' => 'CCTV – 3. Weekly Playback & Log Audit (15 min)',
                'icon' => 'fas fa-history',
                'type' => 'hardware',
                'has_photo' => false,
                'description' => 'Playback validation, retention check, system logs, and UPS.',
                'display_order' => 52,
                'items' => [
                    'Playback test: 1 random camera per branch from yesterday (day and night)',
                    'Recording days available meet the target',
                    'System log reviewed (reboots, disconnects, failed logins)',
                    'UPS on; no unexpected NVR reboot from power cuts',
                ],
            ],
            [
                'name' => 'CCTV – 4. Monthly On-Site Hardware Inspection (Technician)',
                'icon' => 'fas fa-tools',
                'type' => 'hardware',
                'has_photo' => true,
                'description' => 'On-site lens cleaning, angle verification, PoE switches, and SMART health.',
                'display_order' => 53,
                'items' => [
                    'HDD health (SMART), model, power-on hours',
                    'Clean lenses/domes; check IR at night',
                    'Camera angle, mounting, cables and connectors',
                    'PoE switch and NVR ventilation (dust, heat)',
                    'UPS battery and runtime test',
                    'User accounts reviewed',
                ],
            ],
            [
                'name' => 'CCTV – 5. Quarterly Maintenance & Lifecycle Audit',
                'icon' => 'fas fa-calendar-check',
                'type' => 'hardware',
                'has_photo' => false,
                'description' => 'Firmware updates, USB drill exports, drive replacement reviews.',
                'display_order' => 54,
                'items' => [
                    'Firmware update (official Dahua source only; test on one branch first)',
                    'NVR config backup exported',
                    'Footage export to USB drill',
                    'Replace HDDs older than ~3 years or with bad sectors',
                    'Review repeated incidents; decide on hardware replacement',
                ],
            ],
        ];

        foreach ($cctvCategories as $catData) {
            $items = $catData['items'];
            unset($catData['items']);

            $catId = DB::table('it_survey_categories')->where('name', $catData['name'])->value('id');
            if (!$catId) {
                $catId = DB::table('it_survey_categories')->insertGetId(array_merge($catData, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            foreach ($items as $idx => $qText) {
                $exists = DB::table('it_survey_checklist_items')
                    ->where('category_id', $catId)
                    ->where('question', $qText)
                    ->exists();

                if (!$exists) {
                    DB::table('it_survey_checklist_items')->insert([
                        'category_id' => $catId,
                        'question' => $qText,
                        'help_text' => null,
                        'display_order' => $idx + 1,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cctv_incident_logs');
        Schema::dropIfExists('cctv_branch_registers');
    }
};
