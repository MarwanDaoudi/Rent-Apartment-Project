<?php

use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
Route::get('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    /////////////////////////////////////////////////////////////////
    // for admin

    /////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////
    // for landlord مؤجر
    Route::middleware('Role:landlord')->group(function () {
        Route::apiResource('apartment', ApartmentController::class);
        Route::get('BookingsApartment/{id}', [ApartmentController::class, 'showBookingsForApartment']);
        Route::get('ConfirmedBookings/{id}', [ApartmentController::class, 'showConfirmedBookingsForApartment']);
        Route::get('BookingsLandlord', [ApartmentController::class, 'showAllBookings']);
        Route::get('ConfirmedBookingsLandlord', [ApartmentController::class, 'showAllConfirmedBookings']);
        Route::post('confirmBooking/{booking_id}', [ApartmentController::class, 'confirmBooking']);
    });
    /////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////
    // for التنين
    Route::get('profile/{id}',[UserController::class,'show']);
    Route::get('reviews/{apartment_id}', [ReviewController::class, 'indexForApartment']);
    /////////////////////////////////////////////////////////////////
    
    /////////////////////////////////////////////////////////////////
    // for tenant مستاجر
    Route::middleware('Role:tenant')->group(function () {
        Route::prefix('apartments/')->group(function () {
            Route::get('Tenant', [ApartmentController::class, 'indexAll']);
            Route::get('Tenant/{id}', [ApartmentController::class, 'showForTenant']);
            //#######################################################################################
            Route::post('toggleFavourite/{apartmentId}', [ApartmentController::class, 'toggleFavorite']);
            Route::get('favorites', [ApartmentController::class, 'getFavorites']);
            //#######################################################################################
            Route::get('latest', [ApartmentController::class, 'getLastFiveApartment']);
            //#######################################################################################
            Route::apiResource('booking', BookingController::class)->except(['store']);
            Route::post('booking/{apartment_id}', [BookingController::class, 'store']);
            //#######################################################################################
            Route::get('filter',[ApartmentController::class,'filteringApartment']);
            //#######################################################################################
            Route::get('notifications',[NotificationController::class,'index']);
            Route::delete('clear/notifications',[NotificationController::class,'destroy']);
            //#######################################################################################
            Route::apiResource('rate',ReviewController::class)->except(['store']);
            Route::post('rate/{apartment_id}', [ReviewController::class, 'store']);
            //#######################################################################################
            Route::get('{apartment_id}',[AvailabilityController::class,'showAvailabilty']);
        });
    });
    /////////////////////////////////////////////////////////////////
});

