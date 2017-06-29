<?php

namespace App;

use App\Group;
use Illuminate\Database\Eloquent\Model;

class GroupColumn extends Model
{
    protected $fillable = ['name', 'top_parent_id'];

    public $timestamps = false;

    public function group()
    {
        $this->hasOne(Group::class);
    }
}
