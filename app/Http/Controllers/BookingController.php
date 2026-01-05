<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Apartment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Availability;
use Carbon\Carbon;

class BookingController extends Controller
{
    //
    public function hasDateOverlap(int $apartment_id, $new_start, $new_end): bool
    {
        $new_start = Carbon::parse($new_start);
        $new_end   = Carbon::parse($new_end);

        $periods = Availability::where('apartment_id', $apartment_id)->get();

        foreach ($periods as $period) {
            $old_start = Carbon::parse($period->start_non_available_date);
            $old_end   = Carbon::parse($period->end_non_available_date);

            if ($new_start->lt($old_end) && $old_start->lt($new_end)) {
                return true; // في تداخل
            }
        }
        return false;
    }

    public function calculateTotalCost($price_for_month, $start_date, $end_date)
    {
        $start = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);
        $fullMonths = $start->diffInMonths($end);
        if ($start->copy()->addMonths($fullMonths)->lessThan($end)) {
            $fullMonths++;
        }
        if ($fullMonths == 0 && $start->lessThan($end)) {
            $fullMonths = 1;
        }
        return $fullMonths * $price_for_month;
    }

    public function store(StoreBookingRequest $request, int $apartment_id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $validatedData = $request->validated();
        $validatedData['user_id'] = $user_id;
        $validatedData['apartment_id'] = $apartment_id;

        $apartment = Apartment::findOrFail($apartment_id);
        $total_cost = $this->calculateTotalCost($apartment->price_for_month, $request->start_date, $request->end_date);
        $validatedData['total_cost'] = $total_cost;
        if ($user->balance < $total_cost) {
            return response()->json(['message' => 'you can\'t book this apartment because you don\'t have enough balance'], 400);
        }
        if (!$this->hasDateOverlap($apartment_id, $request->start_date, $request->end_date)) {
            $booking = Booking::create($validatedData);
            return response()->json($booking, 201);
        }
        return response()->json(['message' => 'Sorry but the apartment isn\'t availabel.'], 200);
    }

    public function update(UpdateBookingRequest $request, int $id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $booking = Booking::findOrFail($id);
        if ($user_id != $booking->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        if ($booking->status == 'pending') {
            if (!$this->hasDateOverlap($booking->apartment_id, $request->start_date, $request->end_date)) {
                $apartment = Apartment::findOrFail($booking->apartment_id);
                $total_cost = $this->calculateTotalCost($apartment->price_for_month, $request->start_date, $request->end_date);
                if ($user->balance < $total_cost) {
                    return response()->json(['message' => 'you can\'t update this booking because you don\'t have enough balance'], 400);
                }
                $booking->total_cost = $total_cost;
                $booking->save();
                $booking->update($request->validated());
                return response()->json(['message' => 'The booking has updated', 'booking' => $booking], 200);
            }
            return response()->json(['message' => 'You can\'t update this booking because the new date isn\'t availabel'], 200);
        }
        return response()->json(['message' => 'You can\'t update this booking'], 200);
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $booking = Booking::findOrFail($id);
        if ($user_id != $booking->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        if ($booking->status == 'pending') {
            $booking->update([
                'status' => 'canceled'
            ]);
            return response()->json(['message' => 'The booking has canceld'], 204);
        }
        // الحجز موافق عليه و لسا ما بلش (بداية الحجز  اكبر من اليوم لانه مستقبلي )
        if ($booking->status == 'confirmed' && (Carbon::parse($booking->start_date)->gt(today()))) {
            $booking->update([
                'status' => 'canceled'
            ]);
            $halfCost = $booking->total_cost / 2;
            $owner = $booking->apartment->user;
            $owner->increment('balance', $halfCost);
            $user->decrement('balance', $halfCost);
            $availabilty = Availability::where('apartment_id', $booking->apartment_id)
                ->where('start_non_available_date', $booking->start_date)
                ->where('end_non_available_date', $booking->end_date)
                ->delete();
            return response()->json(['message' => 'The booking has canceld'], 204);
        }
        return response()->json(['message' => 'You can\'t cancel this booking']);
    }

    public function show(int $id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $booking = Booking::findOrFail($id);
        if ($user_id != $booking->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        return response()->json($booking, 200);
    }

    public function index()
    {
        $user = Auth::user();
        $bookings = $user->bookings()->with(['apartment:id,city,town,description', 'apartment.images:id,apartment_id,image'])->get();
        return response()->json($bookings, 200);
    }
}
