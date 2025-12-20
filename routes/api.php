<?php

use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingController;
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
    //-----------------------------------------------------
    // for admin
    Route::middleware('Role:admin')->group(function () {
        Route::get('getAllTemporaryUsers', [UserController::class, 'temporaryIndex']);
        Route::post('acceptUser/{id}', [UserController::class, 'acceptUser']);
        Route::get('allusers', [UserController::class, 'index']);
        Route::delete('deleteUser/{id}', [UserController::class, 'destroy']);
    });
    /////////////////////////////////////////////////////////////////


    /////////////////////////////////////////////////////////////////
    // for landlord مؤجر
    Route::middleware('Role:landlord')->group(function () {

        Route::apiResource('apartment', ApartmentController::class);
        Route::get('BookingsApartment/{id}', [ApartmentController::class, 'showBookingsForApartment']);
        Route::get('BookingsLandlord', [ApartmentController::class, 'showAllBookings']);
        Route::post('confirmBooking/{booking_id}', [ApartmentController::class, 'confirmBooking']);
    });
    /////////////////////////////////////////////////////////////////


    /////////////////////////////////////////////////////////////////
    // for tenant مستاجر

    Route::middleware('Role:tenant')->group(function () {

        Route::prefix('apartments/')->group(function () {
            Route::get('Tenant', [ApartmentController::class, 'indexAll']);
            Route::get('Tenant/{id}', [ApartmentController::class, 'showForTenant']);
            Route::post('toggleFavourite/{apartmentId}', [ApartmentController::class, 'toggleFavorite']);
            Route::get('favorites', [ApartmentController::class, 'getFavorites']);
            //#######################################################################################
            Route::apiResource('booking', BookingController::class)->except(['store']);
            Route::post('booking/{apartment_id}', [BookingController::class, 'store']);
            //#######################################################################################
            Route::get('{apartment_id}',[AvailabilityController::class,'showAvailabilty']);
            //#######################################################################################
            Route::apiResource('rate',ReviewController::class)->except(['index']);
            //#######################################################################################
            Route::get('filter',[ApartmentController::class,'filteringApartment']);
        });
    });
});
