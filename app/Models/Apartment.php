<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Apartment extends Model
{
    //
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reveiws()
    {
        return $this->hasMany(Reveiw::class);
    }

    public function availability()
    {
        return $this->hasMany(Availability::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    //////////////////////////////////////////////////////////////////////
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['city'] ?? null, function ($q, $city) {
                $q->where('city', $city);
            })
            ->when($filters['town'] ?? null, function ($q, $town) {
                $q->where('town', $town);
            })
            ->when($filters['min_price'] ?? null, function ($q, $min) {
                $q->where('price_for_month', '>=', $min);
            })
            ->when($filters['max_price'] ?? null, function ($q, $max) {
                $q->where('price_for_month', '<=', $max);
            })
           ->when($filters['min_rooms'] ?? null, function ($q, $min) {
                $q->where('rooms', '>=', $min);
            })
            ->when($filters['max_rooms'] ?? null, function ($q, $max) {
                $q->where('rooms', '<=', $max);
            })
            ->when($filters['min_space'] ?? null, function ($q, $min) {
                $q->where('space', '>=', $min);
            })
            ->when($filters['max_space'] ?? null, function ($q, $max) {
                $q->where('space', '<=', $max);
            })
            ->when($filters['min_rating'] ?? null, function ($q, $rating) {
                $q->where('rating', '>=', $rating);
            });
    }


}
