<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "--- COMPANY SYSTEM USER DETAILS ---\n";
$users = DB::connection('company_system')->table('users as u')
    ->leftJoin('employees as e', 'u.employee_id', '=', 'e.id')
    ->leftJoin('departments as d', 'e.department_id', '=', 'd.id')
    ->leftJoin('positions as p', 'e.position_id', '=', 'p.id')
    ->leftJoin('branches as b', 'e.branch_id', '=', 'b.id')
    ->select(
        'u.id as cs_user_id',
        'u.name as cs_name',
        'u.email as cs_email',
        'u.password as cs_password',
        'u.is_active as cs_user_active',
        'u.telegram_chat_id',
        'e.id as employee_id',
        'e.first_name',
        'e.last_name',
        'e.phone',
        'd.id as dept_id',
        'd.name as dept_name',
        'p.id as pos_id',
        'p.title as pos_title',
        'p.level as pos_level',
        'b.id as branch_id',
        'b.name as branch_name',
        'b.status as branch_status'
    )
    ->get();

echo "Total joined users: " . $users->count() . "\n";

$byDept = [];
$byPos = [];
$activeBranches = [];

foreach ($users as $u) {
    $dept = $u->dept_name ?: 'NO_DEPT';
    $byDept[$dept] = ($byDept[$dept] ?? 0) + 1;
    $pos = $u->pos_title ?: 'NO_POS';
    $byPos[$pos] = ($byPos[$pos] ?? 0) + 1;
    if ($u->branch_name && $u->branch_status === 'active') {
        $activeBranches[$u->branch_name] = true;
    }
}

echo "\n--- DEPARTMENTS IN COMPANY SYSTEM (with user counts) ---\n";
arsort($byDept);
print_r(array_slice($byDept, 0, 20, true));

echo "\n--- TOP POSITIONS IN COMPANY SYSTEM ---\n";
arsort($byPos);
print_r(array_slice($byPos, 0, 20, true));

echo "\n--- TOTAL ACTIVE BRANCHES IN CS: " . count($activeBranches) . " ---\n";

echo "\n--- IT DEPARTMENT USERS IN CS ---\n";
foreach ($users as $u) {
    if (stripos($u->dept_name ?? '', 'IT') !== false || stripos($u->pos_title ?? '', 'IT') !== false) {
        echo "ID: {$u->cs_user_id} | Name: {$u->cs_name} | Email: {$u->cs_email} | Dept: {$u->dept_name} | Pos: {$u->pos_title} | Branch: {$u->branch_name}\n";
    }
}

echo "\n--- FINANCE DEPARTMENT USERS IN CS ---\n";
$finCount = 0;
foreach ($users as $u) {
    if (stripos($u->dept_name ?? '', 'Finance') !== false) {
        $finCount++;
        if ($finCount <= 5) {
            echo "ID: {$u->cs_user_id} | Name: {$u->cs_name} | Email: {$u->cs_email} | Dept: {$u->dept_name} | Pos: {$u->pos_title}\n";
        }
    }
}
echo "Total Finance users in CS: $finCount\n";

echo "\n--- F&B DEPARTMENT USERS IN CS ---\n";
$fbCount = 0;
foreach ($users as $u) {
    if (stripos($u->dept_name ?? '', 'F&B') !== false || stripos($u->dept_name ?? '', 'Food') !== false) {
        $fbCount++;
        if ($fbCount <= 5) {
            echo "ID: {$u->cs_user_id} | Name: {$u->cs_name} | Email: {$u->cs_email} | Dept: {$u->dept_name} | Pos: {$u->pos_title}\n";
        }
    }
}
echo "Total F&B users in CS: $fbCount\n";
