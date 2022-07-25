<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class University extends Model
{
    protected $fillable = ['name', 'slug'];

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'university_groups');
    }

    public function totals()
    {
        return $this->hasMany(Total::class);
    }
}
