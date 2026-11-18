<?php

namespace App\Models;

use App\Events\ConfigChanged;
use App\Events\ConfigWillChange;
use App\Models\Traits\HasMetaFields;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;


class Config extends Model
{
    use HasFactory, HasMetaFields;

    public static function boot()
    {
        parent::boot();
    }

    public static function id($key)
    {
        return static::where('key', $key)->first()?->id;
    }

    public static function get($key)
    {
        try {
            $value = static::where('key', $key)->first()?->value;

            if ($value)
                $value = json_decode($value);

            return $value;
        } catch (Exception $ex) {
            return null;
        }
    }

    public static function set($key, $value)
    {
        $record = static::where('key', $key)->first();

        if (!$record) {
            $record = new static;
            $record->key = $key;
        }

        ConfigWillChange::fire($key, $value);

        if ($value)
            $record->value = json_encode($value);
        else
            $record->value = $value;

        $record->save();

        ConfigChanged::fire($key);

        return $record;
    }
}
