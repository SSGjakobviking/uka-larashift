<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Total extends Model
{
    protected $fillable = [
        'relation_id',
        'relation_type',
        'year',
        'gender',
    ];

    public $timestamps = false;

    /**
     * Retrieve the associated group or dataset model.
     *
     * @return \App\Group|\App\Dataset
     */
    public function relation()
    {
        return $this->morphTo();
    }

    /**
     * Retrieve all of the associated total value models.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function values()
    {
        return $this->hasMany(TotalValue::class)->orderBy('order');
    }
}
