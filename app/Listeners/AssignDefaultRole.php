<?php

namespace App\Listeners;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AssignDefaultRole
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserCreated  $event
     * @return void
     */
    public function handle(Registered $event)
    {
        $event->user;

        $role = Role::where('is_default_role_for_new_signup', true)->first();

        $event->user->roles()->save($role);
    }
}
