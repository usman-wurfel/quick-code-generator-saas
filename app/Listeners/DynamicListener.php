<?php

namespace App\Listeners;

use App\Support\System\Traits\WriteLogs;

abstract class DynamicListener extends Listener
{
    use WriteLogs;

    protected static $listeners = [];

    public static function listen($callback)
    {
        static::$listeners[] = $callback;
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ConfigChanged  $event
     * @return void
     */
    public function handle($event)
    {
        $this->logDebugf('calling regsitered listeners');

        foreach ($this::$listeners as $callback) {
            call_user_func_array($callback, [$event]);
        }
    }
}
