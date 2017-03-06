<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GroupColumn extends Model
{
    protected $fillable = ['name'];

    public $timestamps = false;

}
