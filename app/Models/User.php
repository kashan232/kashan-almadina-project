<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
// use Spatie\Permission\Models\Role;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

//    public function roles()
//     {
//         return $this->belongsToMany(Role::class, 'model_has_roles', 'model_id', 'role_id')
//                     ->where('model_type', User::class);
//     }
    public function userGroups()
    {
        return $this->belongsToMany(UserGroup::class, 'user_group_assignments', 'user_id', 'user_group_id');
    }

    /**
     * Check if user can access the default Shop Stock (ID 0)
     */
    public function canAccessShop()
    {
        if ($this->hasRole('Admin')) {
            return true;
        }
        return $this->userGroups()->where('allow_shop', 1)->exists();
    }
}
