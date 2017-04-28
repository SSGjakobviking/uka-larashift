<?php

namespace App;

use App\Dataset;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name'];
    
    public function datasets()
    {
        return $this->belongsToMany(Dataset::class, 'dataset_tags');   
    }
}
