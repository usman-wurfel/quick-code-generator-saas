<?php

namespace App\Support;

use App\Models\LeadForm;
use App\Models\User;

class LeadFormManager
{
    public function save(
        $id,
        string $related_model_id,
        string $related_model,
        $configs,
        $fields,
        User $user
    ) {

        if (empty($id)) {
            $form = $this->createForm($user);
        } else {
            $form = LeadForm::find($id);
        }

        $form->related_model = $related_model;

        $form->related_model_id = $related_model_id;

        $form->configs = $configs;

        $form->fields = $fields;

        $form->save();

        return $form;
    }

    private function createForm(User $user)
    {
        $form = new LeadForm();

        $form->user_id = $user->id;

        return $form;
    }
}
