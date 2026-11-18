<?php

namespace App\Support\SoftwareUpdate;

use App\Support\SoftwareUpdate\MandatorySeeders\Seeder;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DatabaseUpdateManager
{
    use WriteLogs;

    protected function canMigrate()
    {
        $availableMigrations = count(glob(base_path('database/migrations/*.php')));

        $currentMigrations = DB::table('migrations')->count();

        $hasMigrations = $availableMigrations > $currentMigrations;

        return $hasMigrations;
    }

    protected function numberOfAvailableMigrations()
    {
        $availableMigrations = count(glob(base_path('database/migrations/*.php')));

        $currentMigrations = DB::table('migrations')->count();

        return $availableMigrations - $currentMigrations;
    }

    public function hasDatabaseUpdate()
    {
        return $this->canMigrate() || $this->seederVersionUpgradeRequired();
    }

    public function updateDatabase()
    {
        $migrations = $this->numberOfAvailableMigrations();

        for ($i = 0; $i < $migrations; $i++) {
            try {
                Artisan::call('migrate --force --step');
            } catch (Throwable $th) {
                $this->logWarningf(
                    'Migration running failed: %s',
                    $th->getMessage()
                );
            }
        }

        $this->runSeeders();
    }

    private function seederVersionUpgradeRequired()
    {
        $seeders = $this->makeMandatorySeedersClassList();

        return collect($seeders)
            ->map(fn ($class) => app($class))
            ->reduce(fn ($result, $seeder) => $result || $seeder->versionUpgradeRequired(), false);
    }

    private function runSeeders()
    {
        $seeders = $this->makeMandatorySeedersClassList();

        foreach ($seeders as $class) {

            $className = class_basename($class);

            try {
                Log::info('Running seeder ' . $className);

                $seeder = app($class);

                $seeder->seed();

                Log::info('Seeder completed successfully ' . $className);
            } catch (Throwable $th) {

                Log::error('Failed running seeder ' . $className . ' ' . $th->getMessage());

                Log::debug($th->getTraceAsString());
            }
        }
    }

    private function makeMandatorySeedersClassList()
    {
        $files = array_map(
            function ($file) {
                $file = basename($file, '.php');

                return $file;
            },
            glob(__DIR__ . '/MandatorySeeders/*.php')
        );

        $files = array_filter(
            $files,
            function ($file) {
                return !preg_match('/^Seeder$/', $file);
            }
        );

        return array_map(fn ($f) =>  __NAMESPACE__ . '\\MandatorySeeders\\' . $f, $files);
    }
}
