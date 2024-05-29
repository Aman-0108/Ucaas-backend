<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SofiaGlobalSetting extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    // protected $table = 'sofia_global_settings_clone';
}
