<?php

namespace App\Support\System\Traits;

use Illuminate\Support\Facades\Log;

trait WriteLogs
{
    private function formatLogf()
    {
        return sprintf(
            '%s: %s',
            class_basename(static::class),
            call_user_func_array('sprintf', func_get_args())
        );
    }

    private function formatLog()
    {
        return sprintf(
            '%s: %s',
            class_basename(static::class),
            func_get_arg(0)
        );
    }

    private function logInfo($message)
    {
        Log::info(
            call_user_func_array(
                [$this, 'formatLog'],
                func_get_args()
            )
        );
    }

    private function logDebugf($message)
    {
        Log::debug(
            call_user_func_array(
                [$this, 'formatLogf'],
                func_get_args()
            )
        );
    }

    private function logDebug($message)
    {
        Log::debug(
            call_user_func_array(
                [$this, 'formatLog'],
                func_get_args()
            )
        );
    }

    private function logError($message)
    {
        Log::error(
            call_user_func_array(
                [$this, 'formatLog'],
                func_get_args()
            )
        );
    }

    private function logErrorf($message)
    {
        Log::error(
            call_user_func_array(
                [$this, 'formatLogf'],
                func_get_args()
            )
        );
    }

    private function logWarning($Message)
    {
        Log::warning(
            call_user_func_array(
                [$this, 'formatLog'],
                func_get_args()
            )
        );
    }

    private function logWarningf($message)
    {
        Log::warning(
            call_user_func_array(
                [$this, 'formatLogf'],
                func_get_args()
            )
        );
    }
}
