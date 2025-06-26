<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyMaster extends Model
{
    use HasFactory;
    protected $fillable = [
        'exchange_rate_to_inr',
        'symbol',
    ];
}
