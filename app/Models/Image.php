<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    //
    protected $guarded = ['id'];
    public function apartment() {
        return $this->belongsTo(Apartment::class);
    }
}
