<?php

namespace App;

use App\Group;
use Illuminate\Database\Eloquent\Model;

class Total extends Model
{
    protected $fillable = [
        'dataset_id',
        'group_id',
        'university_id',
        'term',
        'year',
        'gender',
    ];

    public $timestamps = false;

    public function dataset()
    {
        return $this->belongsTo(Dataset::class);
    }

    /**
     * Retrieve all of the associated total value models.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function values()
    {
        return $this->hasMany(TotalValue::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function university()
    {
        return $this->belongsTo(University::class);
    }

}