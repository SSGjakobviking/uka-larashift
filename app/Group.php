<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'dataset_id',
        'parent_id',
        'column_id',
        'name',
        'order',
    ];

    public $timestamps = false;
    
    /**
     * Retrieve the associated dataset model.
     *
     * @return \App\Dataset
     */
    public function dataset()
    {
        return $this->belongsTo(Dataset::class);
    }

    /**
     * Retrieve the associated dataset models.
     *
     * @return \App\Group
     */
    public function parent()
    {
        return $this->belongsTo(Group::class, 'parent_id');
    }

    /**
     * Retrieve all of the associated group models.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function children()
    {
        return $this->hasMany(Group::class, 'parent_id')->orderBy('order');
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

    public function column()
    {
        return $this->hasOne(GroupColumn::class, 'id');
    }
}
