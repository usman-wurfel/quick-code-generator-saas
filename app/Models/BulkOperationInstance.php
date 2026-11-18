<?php

namespace App\Models;

use App\Support\System\Traits\WriteLogs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property array data
 * @property array result
 * @property File file
 * @property int file_id
 * @property string type
 * @property string status
 * @property string name
 * @property int user_id
 * @property BulkOperationResult[] results
 * @property User user
 */
class BulkOperationInstance extends Model
{
    use WriteLogs;

    use HasFactory;

    public const STATUS_NEW = 'new';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public $casts = [
        'data' => 'array',
        'result' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function file()
    {
        return $this->morphOne(File::class, 'attachable');
    }

    public function results()
    {
        return $this->hasMany(BulkOperationResult::class);
    }
}
