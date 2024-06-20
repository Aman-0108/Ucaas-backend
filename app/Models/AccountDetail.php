<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the document details associated with the account.
     */
    public function document()
    {
        return $this->hasOne(Document::class, 'id', 'document_id');
    }
}
