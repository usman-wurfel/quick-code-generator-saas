<?php

namespace App\Support\SystemStatus;

use App\Console\Commands\AppInstall;
use App\Console\Kernel;
use App\Models\Config;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CronjobEntry extends BaseEntry
{
    private $cronLastTime = null;

    public function __construct()
    {
        $this->cronLastTime = Config::get(Kernel::CRON_LAST_RUN_CONFIG_KEY);
    }

    protected function instructionsText()
    {
        return sprintf(
            'Add the following entry to your crontab <code>%s</code>',
            AppInstall::systemCronJobCommand()
        );
    }

    protected function informationText()
    {
        return 'Last run on ' . (new Carbon($this->cronLastTime));
    }

    public function title()
    {
        return 'Cron job';
    }

    public function text()
    {
        return $this->isSuccess() ? 'Running' : 'Not running';
    }

    protected function isSuccess()
    {
        if (!$this->cronLastTime) {
            return false;
        }

        $date = new Carbon($this->cronLastTime);

        return Carbon::now()->diffInMinutes($date) < 30;
    }

    public function sortOrder()
    {
        return 20;
    }
}
