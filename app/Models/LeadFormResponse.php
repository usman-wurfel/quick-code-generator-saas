<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property array fields
 * @property int lead_form_id
 * @property LeadForm lead_form
 */

class LeadFormResponse extends Model
{
    use HasFactory;

    protected $casts = [
        'fields' => 'array'
    ];

    public function lead_form()
    {
        return $this->belongsTo(LeadForm::class);
    }
}
