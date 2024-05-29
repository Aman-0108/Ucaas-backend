<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ringgroup extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function ring_group_destination()
    {
        return $this->hasMany(Ring_group_destination::class, 'ring_group_id', 'id');
    }
}
