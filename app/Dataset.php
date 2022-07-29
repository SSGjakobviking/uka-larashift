<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    protected $fillable = [
        'user_id',
        'indicator_id',
        'file',
        'year',
        'version',
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

    public function statuses()
    {
        return $this->belongsToMany(Status::class)->withTimestamps();
    }
}
