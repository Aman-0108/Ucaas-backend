<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagUser extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function tag()
    {
        return $this->hasMany(Tag::class, 'id', 'tag_id');
    }
}
