<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property array configs
 * @property array fields
 * @property User user
 * @property string related_model_id
 * @property string related_model
 * @property int user_id
 */
class LeadForm extends Model
{
    use HasFactory;

    protected $casts = [
        'configs' => 'array',
        'fields' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
