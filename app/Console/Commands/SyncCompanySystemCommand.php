<?php

namespace App\Console\Commands;

use App\Services\CompanySystemSyncService;
use Illuminate\Console\Command;

class SyncCompanySystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:company-system {--no-cleanup : Do not delete old system placeholder users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync users with passwords, departments, and active branches from Company System database';

    /**
     * Execute the console command.
     */
    public function handle(CompanySystemSyncService $syncService): int
    {
        $this->info("Starting Company System synchronization...");
        $startTime = microtime(true);

        $cleanOld = !$this->option('no-cleanup');

        $result = $syncService->syncAll($cleanOld);

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info(" Synchronization completed in {$elapsed}s");
        $this->table(
            ['Entity', 'Count / Details', 'Status'],
            [
                ['Departments', $result['departments']['total_synced'] ?? 0, $result['departments']['status'] ?? 'unknown'],
                ['Active Branches', $result['branches']['active_branches_synced'] ?? 0, $result['branches']['status'] ?? 'unknown'],
                ['Users Created', $result['users']['users_created'] ?? 0, $result['users']['status'] ?? 'unknown'],
                ['Users Updated', $result['users']['users_updated'] ?? 0, $result['users']['status'] ?? 'unknown'],
                ['Total Users Processed', $result['users']['total_processed'] ?? 0, 'complete'],
            ]
        );

        return Command::SUCCESS;
    }
}
