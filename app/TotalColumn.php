<?php

namespace App;

use App\TotalValue;
use Illuminate\Database\Eloquent\Model;

class TotalColumn extends Model
{
    protected $fillable = ['name'];

    public $timestamps = false;

    public function values()
    {
        return $this->belongsTo(TotalValue::class);
    }
}
