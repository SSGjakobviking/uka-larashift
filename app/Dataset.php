<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{

    protected $fillable = [
        'indicator_id',
        'file',
        'version',
        'status',
    ];

    public $timestamps = false;

    /**
     * Retrieve the associated indicator models.
     *
     * @return \App\Indicator
     */
    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }

    /**
     * Retrieve all of the associated group models.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function groups()
    {
        return $this->hasMany(Group::class, 'dataset_id')->where('parent_id', null);
    }

    /**
     * Retrieve all of the associated total models.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function totals()
    {
        return $this->morphMany(Total::class, 'relation');
    }
}
