<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function statuses()
    {
        return $this->hasMany(MessageStatus::class, 'message_uuid', 'uuid');
    }
}
