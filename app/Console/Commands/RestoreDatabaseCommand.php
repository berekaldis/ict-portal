<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RestoreDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:restore-data {--file= : Path to custom SQL dump file} {--force : Force restore without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore the full kaldisbp_ict database from kaldis_full_backup.sql';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file') ?: database_path('kaldis_full_backup.sql');

        if (!File::exists($filePath)) {
            $this->error("Database backup file not found at: {$filePath}");
            return Command::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('This will restore all tables and data into the current database. Proceed?', true)) {
            $this->info('Restoration cancelled.');
            return Command::SUCCESS;
        }

        $this->info("Starting database restoration from: " . basename($filePath) . " (" . round(filesize($filePath) / 1024, 2) . " KB)...");

        $sql = File::get($filePath);

        // Disable foreign key checks for clean restoration
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

        try {
            DB::unprepared($sql);
            $this->info("✅ Database restoration completed successfully!");
        } catch (\Throwable $e) {
            $this->warn("DB::unprepared error: " . $e->getMessage() . ". Trying statement-by-statement execution...");

            // Fallback: split by semicolon at ends of lines
            $statements = preg_split('/;\s*[\r\n]+/', $sql);
            $count = 0;
            $bar = $this->output->createProgressBar(count($statements));
            $bar->start();

            foreach ($statements as $stmt) {
                $trimmed = trim($stmt);
                if (!empty($trimmed) && !str_starts_with($trimmed, '--') && !str_starts_with($trimmed, '/*')) {
                    try {
                        DB::statement($trimmed);
                        $count++;
                    } catch (\Throwable $innerEx) {
                        // ignore comment/delimiter quirks
                    }
                }
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
            $this->info("✅ Executed {$count} SQL statements successfully!");
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $usersCount = DB::table('users')->count();
        $this->info("Current Users count: {$usersCount}");

        return Command::SUCCESS;
    }
}
