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
        return $query->status('published');
    }

    /**
     * Retrieve datasets set for preview.
     *
     * @param  [type] $query
     * @return [type]
     */
    public function scopePreview($query)
    {
        return $query->status('preview');
    }

    public function scopeNotPreview($query)
    {
        return $query->where('name', '<>', 'preview');
    }

    public function scopeOrProcessing($query)
    {
        return $query->orWhere('name', 'processing');
    }

    public function scopeNotPreviewAndPublished($query)
    {
        return $query
                ->where('name', '<>', 'preview')
                ->where('name', '<>', 'published');
    }

    public function scopeNotPublished($query)
    {
        return $query->where('name', '<>', 'published');
    }
}
