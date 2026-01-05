<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Apartment;
use App\Models\Booking;
use App\Models\Reveiw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReviewController extends Controller
{
    //
    public function calculateApartmentRate(int $apartment_id)
    {
        $apartment = Apartment::findOrFail($apartment_id);
        $average = Reveiw::where('apartment_id', $apartment_id)->avg('rate');
        $apartment->rating = round($average ?? 0, 1);
        $apartment->save();
    }

    public function store(StoreReviewRequest $request, int $apartment_id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $validatedData = $request->validated();
        $validatedData['user_id'] = $user_id;
        $validatedData['apartment_id'] = $apartment_id;
        $hasExpiredBooking = Booking::where('user_id', $user->id)
            ->where('apartment_id', $apartment_id)
            ->whereDate('end_date', '<', now())
            ->exists();
        if (!$hasExpiredBooking) {
            return response()->json(['you can\'t rate this apartment because your booking didn\'t expired or you didn\'t booked it']);
        }
        $review  = Reveiw::create($validatedData);
        $this->calculateApartmentRate($apartment_id);
        return response()->json(['message' => 'Thanks for your review', 'review' => $review], 201);
    }

    public function update(UpdateReviewRequest $request, int $id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $review = Reveiw::findOrFail($id);
        if ($user_id != $review->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        $review->update($request->validated());
        $this->calculateApartmentRate($review->apartment_id);
        return response()->json(['reveiw' => $review], 200);
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $review = Reveiw::findOrFail($id);
        if ($user_id != $review->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        $review->delete();
        return response()->json(['message' => 'The reveiw has deleted'], 204);
    }

    public function show(int $id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $review = Reveiw::findOrFail($id);
        if ($user_id != $review->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        return response()->json(['reveiw' => $review], 200);
    }

    public function indexForApartment(int $apartment_id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $apartment = Apartment::findOrFail($apartment_id);
        if ($user->role === "tenant" || $apartment->user_id === $user_id) {
            $reviews = Reveiw::where('apartment_id', $apartment_id)->get();
            return response()->json(['revewis' => $reviews], 200);
        }
        return response()->json(['message' => 'Unauthurized'], 403);
    }
}
