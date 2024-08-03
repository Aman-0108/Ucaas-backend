<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DidDetail extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function dialplan()
    {
        return $this->hasOne(Dialplan::class, 'id', 'id');
    }
}
