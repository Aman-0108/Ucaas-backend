<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the payment associated with the account.
     */
    public function account()
    {
        return $this->hasMany(Account::class, 'id', 'account_id');
    }

    /**
     * Get the billing address associated with the payment.
     */
    public function billingAddress()
    {
        return $this->hasOne(BillingAddress::class, 'id', 'billing_address_id');
    }

    /**
     * Get the card details associated with the payment.
     */
    public function cardDetails()
    {
        return $this->hasOne(CardDetail::class, 'id', 'card_id');
    }

    /**
     * Get the subscription details associated with the payment.
     */
    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'transaction_id', 'transaction_id');
    }
}
