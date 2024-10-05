<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IvrMaster extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the options for the IVR Master
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function options(): HasMany
    {
        return $this->hasMany(IvrOptions::class, 'ivr_id', 'id');
    }
}
