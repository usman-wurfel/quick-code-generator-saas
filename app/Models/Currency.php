<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $currency_code
 * @property string $symbol
 */
class Currency extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'currency_code', 'symbol', 'symbol_position'];
}
