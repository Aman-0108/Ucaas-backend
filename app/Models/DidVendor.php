<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DidVendor extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the rates associated with the vendor.
     */
    public function rates()
    {
        return $this->hasMany(DidRateChart::class, 'vendor_id', 'id');
    }
}
