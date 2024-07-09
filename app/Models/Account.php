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

    /**
     * Get the billing address associated with the payment.
     */
    public function billingAddress()
    {
        return $this->hasMany(BillingAddress::class, 'account_id', 'id');
    }

    /**
     * Get the card details associated with the payment.
     */
    public function cardDetails()
    {
        return $this->hasMany(CardDetail::class, 'account_id', 'id');
    }

    /**
     * Get the subscription details associated with the payment.
     */
    public function subscription()
    {
        return $this->hasMany(Subscription::class, 'account_id', 'id');
    }

    /**
     * Get the extensions associated with the account.
     */
    public function extensions()
    {
        return $this->hasMany(Extension::class, 'account_id', 'id');
    }

    /**
     * Get the users associated with the account.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'account_id', 'id');
    }

    /**
     * Get the DID's associated with the account.
     */
    public function dids()
    {
        return $this->hasMany(DidDetail::class, 'account_id', 'id');
    }
}
