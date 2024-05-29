<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ring_group_destination extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function ringgroup()
    {
        return $this->belongsTo(Ringgroup::class, 'id', 'id');
    }
}
