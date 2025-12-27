<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Booking extends Model
{
    //
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }




    protected static function booted()
    {
        static::saving(function ($booking) {
            $start = Carbon::parse($booking->start_date);
            $end   = Carbon::parse($booking->end_date);
    
            $days = $start->diffInDays($end);
            $months = (int) ceil($days / 30);
    
            $booking->total_cost =
                $months * $booking->apartment->monthly_price;
        });
    }

    
}
