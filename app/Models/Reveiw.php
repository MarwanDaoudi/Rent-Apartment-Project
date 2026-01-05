<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reveiw extends Model
{
    //
    protected $guarded = ['id'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function apartment() {
        return $this->belongsTo(Apartment::class);
    }
}
