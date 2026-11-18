<?php

namespace App\Support;

use App\Events\LeadFormResponseReceived;
use App\Models\LeadForm;
use App\Models\LeadFormResponse;
use App\Models\User;
use App\Notifications\Dynamic\LeadFormResponseNotification;
use Illuminate\Support\Facades\Notification;

class LeadFormResponseManager
{
    public function store($lead_form_id, $fields)
    {
        $response = new LeadFormResponse();

        $response->lead_form_id = $lead_form_id;

        $response->fields = $fields;

        $response->save();

        event(new LeadFormResponseReceived($response));

        return $response;
    }

    public function ofForm(LeadForm $leadForm)
    {
        return LeadFormResponse::where('lead_form_id', $leadForm->id)->get();
    }
}
