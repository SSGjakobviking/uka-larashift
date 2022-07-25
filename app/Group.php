<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'column_id',
        'name',
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
        return $this->hasMany(Group::class, 'parent_id');
    }

    /**
     * Retrieve all of the associated total models.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function totals()
    {
        return $this->hasMany(Total::class);
    }

    public function column()
    {
        return $this->belongsTo(GroupColumn::class);
    }

    public function scopeTopLevel($query, $year, $gender)
    {
        return $query->where('parent_id', null)->children();
    }

    public function universities()
    {
        return $this->belongsToMany(University::class, 'university_groups');
    }
}
