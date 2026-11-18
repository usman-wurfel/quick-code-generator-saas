<?php

namespace App\Support\AI;

use App\Models\SubscriptionPlan;
use App\Console\Kernel;
use App\Interfaces\UserManager;
use App\Models\User;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Console\Scheduling\Schedule;
use Throwable;

class UsageManager
{
    use WriteLogs;

    private User $user;

    private UserManager $users;

    public static function boot()
    {
        if (!config('app.installed')) return;

        try {
            static::bootSchedule();
        } catch (Throwable $th) {
            //
        }
    }

    private static function bootSchedule()
    {
        Kernel::addSchedule(function (Schedule $schedule) {
            $schedule->call(function () {

                try {

                    static::resetMonthlyUsage();
                    //
                } catch (Throwable $th) {

                    (new static)->logWarningf($th->getMessage());
                    //
                }
            })->monthlyOn(1);
        });
    }

    public static function forUser(User $user)
    {
        $instance = new static;

        $instance->user = $user;

        return $instance;
    }

    public static function resetMonthlyUsage()
    {
        $users = static::getAllUsers();

        foreach ($users as $user) {
            static::forUser($user)->resetUsage();
        }
    }

    private static function getAllUsers()
    {
        return (new User)->newModelQuery()->select('id')->get();
    }

    public function __construct()
    {
        $this->users = app(UserManager::class);
    }

    private function resetUsage()
    {
        $this->setUsage(0);

        $this->logInfo('Monthly usage has been reset for user ' . $this->user->email);
    }

    public function increaseUsage()
    {
        $value = $this->getUsage();

        $this->setUsage($value + 1);
    }

    public function getUsage()
    {
        return $this->user->getMeta($this->usageKey()) ?? 0;
    }

    private function setUsage($value)
    {
        $this->user->setMeta($this->usageKey(), $value);
    }

    private function usageKey()
    {
        return static::class . '::aiUsage';
    }
}
