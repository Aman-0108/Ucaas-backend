<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Extension extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    /**
     * Get the follow me details associated with the extension.
     */
    public function followmes()
    {
        return $this->hasMany(Followme::class, 'extension_id', 'id');
    }

    /**
     * Get the domain details associated with the extension.
     */
    public function domain()
    {
        return $this->hasOne(Domain::class, 'id', 'domain');
    }

    // Define the relationship with Provisioning
    public function provisionings()
    {
        return $this->hasMany(Provisioning::class, 'account_id', 'account_id')
            ->where('address', $this->extension);
    }
}
