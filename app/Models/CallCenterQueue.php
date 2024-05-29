<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallCenterQueue extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the agents associated with the queue.
     */
    public function agents()
    {
        return $this->hasMany(CallCenterAgent::class, 'call_center_queue_id', 'id');
    }
}
