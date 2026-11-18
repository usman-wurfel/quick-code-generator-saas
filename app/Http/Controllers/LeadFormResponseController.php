<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadFormResponseRequest;
use App\Http\Requests\UpdateLeadFormResponseRequest;
use App\Models\LeadForm;
use App\Models\LeadFormResponse;
use App\Support\LeadFormResponseManager;

class LeadFormResponseController extends Controller
{
    private LeadFormResponseManager $leads;

    public function __construct(LeadFormResponseManager $leads)
    {
        $this->leads = $leads;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreLeadFormResponseRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLeadFormResponseRequest $request)
    {
        return $this->leads->store(
            lead_form_id: $request->input('lead_form_id'),
            fields: json_decode($request->input('fields'))
        );
    }

    public function ofLeadForm(LeadForm $leadForm)
    {
        return $this->leads->ofForm($leadForm);
    }
}
