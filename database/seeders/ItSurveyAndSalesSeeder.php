<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ItSurveyAndSalesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Permissions
        $permissions = [
            'it_survey.view',
            'it_survey.create',
            'it_survey.manage',
            'cctv_audit.view',
            'cctv_audit.manage',
            'sales_case.view',
            'sales_case.create',
            'sales_case.manage',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // Assign to Super Admin & IT Admin
        $roles = Role::whereIn('name', ['Super Admin', 'IT Admin', 'Admin'])->get();
        foreach ($roles as $role) {
            $role->givePermissionTo($permissions);
        }

        // Also assign all permissions to users with Super Admin or IT Admin role
        $adminUsers = \App\Models\User::all();
        foreach ($adminUsers as $user) {
            if ($user->hasRole('Super Admin') || $user->hasRole('IT Admin')) {
                $user->givePermissionTo($permissions);
            }
        }

        // 2. Default Hardware Survey Categories & Checklist Items
        $categories = [
            [
                'name' => 'POS Terminal & Cash Drawer',
                'icon' => 'fas fa-cash-register',
                'type' => 'hardware',
                'has_photo' => true,
                'description' => 'Touch terminals, customer displays, and cash drawers inspection.',
                'display_order' => 1,
                'items' => [
                    ['question' => 'Is the POS touch screen clean, responsive, and free from cracks or physical damage?'],
                    ['question' => 'Does the cash drawer open automatically and smoothly on cash invoice printing?'],
                    ['question' => 'Are all power and peripheral cables neatly routed, secured, and undamaged?'],
                    ['question' => 'Is the barcode scanner responsive and reading product barcodes without delay?'],
                ],
            ],
            [
                'name' => 'Thermal Receipt & Kitchen Printers',
                'icon' => 'fas fa-print',
                'type' => 'hardware',
                'has_photo' => true,
                'description' => 'Receipt printers, kitchen order printers, paper cutters, and rollers.',
                'display_order' => 2,
                'items' => [
                    ['question' => 'Does the thermal receipt printer produce clean, legible prints without fading?'],
                    ['question' => 'Does the automatic paper cutter work smoothly without jamming?'],
                    ['question' => 'Is the network/USB cable securely attached to the printer?'],
                    ['question' => 'Is there adequate thermal receipt paper stock stored near the station?'],
                ],
            ],
            [
                'name' => 'Network, Switch & Internet Gateway',
                'icon' => 'fas fa-network-wired',
                'type' => 'hardware',
                'has_photo' => true,
                'description' => 'Branch switches, Wi-Fi access points, router/modem, and structured cabling.',
                'display_order' => 3,
                'items' => [
                    ['question' => 'Are all Ethernet patch cables properly labeled and plugged into the switch/router?'],
                    ['question' => 'Is the primary Internet connection stable and responding with low ping latency?'],
                    ['question' => 'Are branch Wi-Fi access points powered and broadcasting properly?'],
                    ['question' => 'Is the network rack/enclosure locked, ventilated, and free of dust build-up?'],
                ],
            ],
            [
                'name' => 'Power Backup & UPS Systems',
                'icon' => 'fas fa-bolt',
                'type' => 'hardware',
                'has_photo' => true,
                'description' => 'Uninterruptible Power Supply (UPS) units, battery runtime, and wall sockets.',
                'display_order' => 4,
                'items' => [
                    ['question' => 'Is the UPS functioning properly on mains power without error beeps or fault lights?'],
                    ['question' => 'Does the UPS hold the POS system during simulated power failover?'],
                    ['question' => 'Are all UPS power cables and surge protectors firmly connected?'],
                ],
            ],
            [
                'name' => 'CCTV Security & Surveillance',
                'icon' => 'fas fa-video',
                'type' => 'hardware',
                'has_photo' => true,
                'description' => 'Security cameras, NVR/DVR recording, and monitor status.',
                'display_order' => 5,
                'items' => [
                    ['question' => 'Are all branch security cameras online with clean, unobstructed lenses?'],
                    ['question' => 'Is the DVR/NVR actively recording and storing history as per company policy?'],
                    ['question' => 'Is the CCTV live monitoring screen working and showing all camera feeds?'],
                ],
            ],
            // Software categories
            [
                'name' => 'CNET ERP & POS Application',
                'icon' => 'fas fa-laptop-code',
                'type' => 'software',
                'has_photo' => false,
                'description' => 'CNET POS version, database connectivity, and synchronization.',
                'display_order' => 6,
                'items' => [
                    ['question' => 'Is CNET ERP running the approved standard version across all terminals?'],
                    ['question' => 'Are offline transactions syncing properly with the central server?'],
                    ['question' => 'Can cashiers login without database connection timeouts?'],
                ],
            ],
            [
                'name' => 'SQL Database & Backups',
                'icon' => 'fas fa-database',
                'type' => 'software',
                'has_photo' => false,
                'description' => 'Local database health, transaction logs, and daily automated backup verification.',
                'display_order' => 7,
                'items' => [
                    ['question' => 'Is the SQL Server service running smoothly without memory or disk exhaustion?'],
                    ['question' => 'Was yesterday\'s automated daily database backup created and verified?'],
                    ['question' => 'Are transaction log files within acceptable size limits?'],
                ],
            ],
            [
                'name' => 'Fiscal E-Tax & Daily Z-Report Closing',
                'icon' => 'fas fa-file-invoice-dollar',
                'type' => 'software',
                'has_photo' => false,
                'description' => 'Fiscal printer service, daily Z-report generation, and tax compliance.',
                'display_order' => 8,
                'items' => [
                    ['question' => 'Did the daily fiscal closing and Z-Report print successfully at end of day?'],
                    ['question' => 'Are fiscal memory and EJ (Electronic Journal) storage within valid limits?'],
                    ['question' => 'Is the fiscal printer driver communicating without serial port errors?'],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $items = $catData['items'];
            unset($catData['items']);

            $cat = DB::table('it_survey_categories')->where('name', $catData['name'])->first();
            if (!$cat) {
                $catId = DB::table('it_survey_categories')->insertGetId(array_merge($catData, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            } else {
                $catId = $cat->id;
            }

            foreach ($items as $idx => $item) {
                $exists = DB::table('it_survey_checklist_items')
                    ->where('category_id', $catId)
                    ->where('question', $item['question'])
                    ->first();
                if (!$exists) {
                    DB::table('it_survey_checklist_items')->insert([
                        'category_id' => $catId,
                        'question' => $item['question'],
                        'help_text' => $item['help_text'] ?? null,
                        'display_order' => $idx + 1,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // 3. Seed initial Sales Case sequence if empty
        if (DB::table('sales_case_sequence')->count() === 0) {
            DB::table('sales_case_sequence')->insert([
                'case_number' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
