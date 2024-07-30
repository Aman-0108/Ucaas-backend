<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingProfile extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function ratingPlan()
    {
        return $this->hasOne(RatingPlan::class, 'id', 'RatingPlanId');
    }
}
