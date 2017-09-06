<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndicatorGroup extends Model
{
    public function indicators()
    {
        return $this->hasMany(Indicator::class, 'indicator_group');
    }
}
