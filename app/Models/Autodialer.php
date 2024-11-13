<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Autodialer extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the members associated with the dialer.
     */
    public function members()
    {
        return $this->hasMany(DialerMember::class, 'autodialers_id', 'id');
    }
}
