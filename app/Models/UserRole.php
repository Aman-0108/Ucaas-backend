<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function roles()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    // public function permissions()
    // {
    //     return $this->hasMany(RolePermission::class, 'role_id', 'role_id');
    // }
}
