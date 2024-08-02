<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dialplan extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function did()
    {
        return $this->hasOne(DidDetail::class, 'did_id', 'id');
    }
}
