<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TotalValue extends Model
{

    protected $fillable = [
        'total_id',
        'column_id',
        'value',
        'order',
    ];

    public $timestamps = false;

    /**
     * Retrieve the associated total model.
     *
     * @return \App\Total
     */
    public function total()
    {
        return $this->belongsTo(Total::class);
    }

    public function column()
    {
        return $this->hasOne(TotalColumn::class, 'id');
    }
}
