<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    use HasFactory;

    protected $fillable = ['group_name'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_group_assignments', 'user_group_id', 'user_id');
    }
}
