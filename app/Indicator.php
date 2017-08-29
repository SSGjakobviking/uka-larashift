<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{

    protected $fillable = ['indicator_group', 'name', 'description', 'slug', 'measurement', 'time_unit'];
    
    /**
     * Retrieve all of the associated dataset models.
     *
     * @return \App\Dataset
     */
    public function datasets()
    {
        return $this->hasMany(Dataset::class);
    }

    /**
     * Retrieve the indicator group.
     * 
     * @return [type]
     */
    public function indicatorGroup()
    {
        return $this->belongsTo(IndicatorGroup::class, 'indicator_group', 'id');
    }
}
