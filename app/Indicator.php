<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    /**
     * Retrieve all of the associated dataset models.
     *
     * @return \App\Dataset
     */
    public function datasets()
    {
        return $this->hasMany(Dataset::class);
    }
}
