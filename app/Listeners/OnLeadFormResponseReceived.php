<?php

namespace App\Listeners;

use App\Events\LeadFormResponseReceived;
use App\Models\LeadFormResponse;
use App\Models\User;
use App\Notifications\Dynamic\LeadFormResponseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OnLeadFormResponseReceived implements ShouldQueue
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
     * @param  \App\Events\LeadFormResponseReceived  $event
     * @return void
     */
    public function handle(LeadFormResponseReceived $event)
    {
        $this->notifyRecepientIfSpecified($event->response);
    }

    private function notifyRecepientIfSpecified(LeadFormResponse $leadFormResponse)
    {
        if (app()->environment('local')) return;

        $form = $leadFormResponse->lead_form;

        $email = @$form->configs['recepient_email'];

        if (!$email) return;

        $user = new User();

        $user->email = $email;

        $user->notifyNow(LeadFormResponseNotification::instance($leadFormResponse));
    }
}
