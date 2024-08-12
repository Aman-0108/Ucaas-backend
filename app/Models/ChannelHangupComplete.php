<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChannelHangupComplete extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];    

    // public function scopeMale($query)
    // {
    //     return $query->where('gender', '=', 'male');
    // }

    public function callerUser()
    {
        return $this->hasOne(User::class, 'id', 'caller_user_id');
    }

    public function calleeUser()
    {
        return $this->hasOne(User::class, 'id', 'callee_user_id');
    }
    
}
