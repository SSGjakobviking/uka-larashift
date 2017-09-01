<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $fillable = ['name'];

    public function datasets()
    {
        return $this->belongsToMany(Dataset::class)->withTimestamps();
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('name', $status);
    }

    /**
     * Retrieve published datasets.
     * 
     * @param  [type] $query
     * @return [type]
     */
    public function scopePublished($query)
    {
        return $query->where('name', 'published');
    }

    /**
     * Retrieve datasets set for preview.
     * 
     * @param  [type] $query
     * @return [type]
     */
    public function scopePreview($query)
    {
        return $query->where('name', 'preview');
    }
}
