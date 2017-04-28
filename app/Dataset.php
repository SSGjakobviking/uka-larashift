<?php

namespace App;

use App\Tag;
use App\User;
use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{

    protected $fillable = [
        'user_id',
        'indicator_id',
        'file',
        'version',
        'status',
    ];

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
     * Retrieve all of the associated total models.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function totals()
    {
        return $this->hasMany(Total::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'dataset_tags');
    }

    /**
     * Retrieve published datasets.
     * 
     * @param  [type] $query
     * @return [type]
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Retrieve datasets set for preview.
     * 
     * @param  [type] $query
     * @return [type]
     */
    public function scopePreview($query)
    {
        return $query->where('status', 'preview');
    }

    public function scopeUnattached($query)
    {
        return $query->where('status', null);
    }
}
