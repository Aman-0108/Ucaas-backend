<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingPlan extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    public function destinationRate()
    {
        return $this->hasOne(DestinationRate::class, 'id', 'DestinationRatesId');
    }
}
