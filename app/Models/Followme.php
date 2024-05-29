<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Followme extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**
     * Get the extension that owns this details.
     */
    public function extension()
    {
        return $this->belongsTo(Extension::class, 'id', 'id');
    }
}
