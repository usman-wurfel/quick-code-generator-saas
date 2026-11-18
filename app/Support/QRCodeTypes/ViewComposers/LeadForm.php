<?php

namespace App\Support\QRCodeTypes\ViewComposers;

class LeadForm extends Base
{
    public static function type()
    {
        return 'lead-form';
    }

    public function leadFormId()
    {
        return (int) $this->designValue('lead_form_id');
    }
}
