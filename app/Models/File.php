<?php

namespace App\Models;

use App\Traits\HasUniqueSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @property string name
 * @property string type
 * @property string mime_type
 * @property string attachable_type
 * @property string path
 * @property int attachable_id
 * @property int user_id
 */
class File extends Model
{
    use HasFactory, HasUniqueSlug;

    protected $fillable = [
        'name', 'type', 'mime_type', 'attachable_type', 'attachable_id', 'user_id'
    ];

    protected $hidden = ['path', 'attachable_type', 'attachable_id'];
}
