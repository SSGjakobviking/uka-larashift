<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndicatorGroup extends Model
{
    protected $fillable = ['name'];
    
    public function indicators()
    {
        return $this->hasMany(Indicator::class, 'indicator_group');
    }
}
