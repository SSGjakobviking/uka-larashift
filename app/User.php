<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function datasets()
    {
        return $this->hasMany(Dataset::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole($name)
    {
        if ($this->role->name === $name) {
            return true;
        }

        return false;
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }
}
