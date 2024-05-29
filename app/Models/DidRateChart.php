<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DidRateChart extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the vandor associated with the vendor.
     */
    public function vendor()
    {
        return $this->hasOne(DidVendor::class, 'id', 'vendor_id');
    }
}
