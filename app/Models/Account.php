<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Account extends Authenticatable
{
    use Notifiable, HasApiTokens, HasFactory, SoftDeletes;

    protected $guarded = [];

    /**
     * Get the details associated with the account.
     */
    public function details()
    {
        return $this->hasMany(AccountDetail::class, 'account_id', 'id');
    }

    /**
     * Get the timezone details associated with the account.
     */
    public function timezone()
    {
        return $this->hasOne(Timezone::class, 'id', 'timezone_id');
    }

    /**
     * Get the package details associated with the account.
     */
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    /**
     * Get the balance associated with the account.
     */
    public function balance()
    {
        return $this->hasOne(AccountBalance::class, 'account_id', 'id');
    }

    /**
     * Get the payment associated with the account.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'account_id', 'id');
    }
}
