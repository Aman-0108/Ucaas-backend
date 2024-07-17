<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function timezone() {
        return $this->hasOne(Timezone::class, 'id', 'timezone_id');
    }
}
