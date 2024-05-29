<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**
     * Get the feature details associated with the package.
     */
    public function features()
    {
        return $this->hasMany(Feature::class, 'package_id', 'id');
    }
}
