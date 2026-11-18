<?php

namespace App\Http\Controllers;

use App\Models\LeadForm;
use App\Support\LeadFormManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadFormController extends Controller
{
    private LeadFormManager $leadForms;

    public function __construct()
    {
        $this->leadForms = new LeadFormManager();
    }

    public function show(LeadForm $leadForm)
    {
        return $leadForm;
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, LeadForm $leadForm)
    {
        return $this->save($request, $leadForm->id);
    }

    private function save(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'related_model_id' => 'required',
            'related_model' => 'required',
        ]);

        $validator->after(function () use ($validator, $request) {
            $modelClass = sprintf('\\App\\Models\\%s', $request->input('related_model'));

            if (!class_exists($modelClass)) {
                $validator->errors()->add('related_model', t('Invalid related model'));
            }

            $model = $modelClass::find($request->input('related_model_id'));

            if (!$model) {
                $validator->errors()->add('related_model_id', t('Not found'));
            }
        });

        $validator->validate();

        return $this->leadForms->save(
            id: $id,
            related_model: $request->input('related_model'),
            related_model_id: $request->input('related_model_id'),
            configs: $request->input('configs'),
            fields: $request->input('fields'),
            user: $request->user()
        );
    }
}
