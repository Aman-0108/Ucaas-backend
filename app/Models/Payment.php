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
     * Get the payment details associated with the payment.
     */
    public function paymentDetails()
    {
        return $this->hasOne(TransactionDetail::class, 'transaction_id', 'transaction_id');
    }
}
