<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApartmentRequest;
use App\Http\Requests\UpdateApartmentRequest;
use App\Models\Apartment;
use App\Models\Availability;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApartmentController extends Controller
{
    //
    public function store(StoreApartmentRequest $request) {
        $user = Auth::user();
        $user_id = $user->id;
        $validatedData = $request->validated();
        $validatedData['user_id'] = $user_id;
        $apartment = Apartment::create($validatedData);
        return response()->json($apartment,200);
    }

    public function update(UpdateApartmentRequest $request,int $id) {
        $user = Auth::user();
        $user_id = $user->id;
        $apartment = Apartment::findOrFail($id);
        if ($user_id != $apartment->user_id) {
            return response()->json(['message'=>'Unauthurized'],403);
        }
        $apartment->update($request->validated());
        return response()->json($apartment,200);
    }

    //للمؤجر مشان يشوف الشقق يلي عندو ياها
    public function index() {
        $user = Auth::user();
        $apartments = $user->apartments;
        return response()->json($apartments,200);
    }

    //مشان المؤجر يشوف تفاصيل الشقة تبعو
    public function show(int $id) {
        $user = Auth::user();
        $user_id = $user->id;
        $apartment = Apartment::findOrFail($id);
        if ($user_id != $apartment->user_id) {
            return response()->json(['message'=>'Unauthurized'],403);
        }
        return response()->json($apartment,200);
    }

    //مشان صاجب لشقة يحذفها اذا بدو
    public function destroy(int $id) {
        $user = Auth::user();
        $user_id = $user->id;
        $apartment = Apartment::findOrFail($id);
        if ($user_id != $apartment->user_id) {
            return response()->json(['message'=>'Unauthurized'],403);
        }
        $apartment->delete();
        return response()->json(['message'=>'The apartment deleted'],204);
    }

    //####################################################################
    //اذا بدي اعرض للمؤجر كل الحجوزات على شقة معينة الو
    public function showBookingsForApartment(int $id) {
        $user = Auth::user();
        $user_id = $user->id;
        $apartment = Apartment::findOrFail($id);
        if ($user_id != $apartment->user_id) {
            return response()->json(['message'=>'Unauthurized'],403);
        }
        return response()->json(['bookings' => $apartment->bookings],200);
    }

    //مشان اعرض للمؤجر كلشي حجوزات عندو ياها للشقق تبعوتو
    public function showAllBookings() {
        $user = Auth::user();
        $bookings = $user->apartments->flatMap->bookings;
        return response()->json($bookings,200);
    }

    // مشان المؤجر يوافق عالحجز او لا
    public function confirmBooking(Request $request, int $booking_id) {
        $booking = Booking::findOrFail($booking_id);
        
        $apartment_id = $booking->apartment_id;

        $user = Auth::user();
        $user_id = $user->id;
        $apartment = Apartment::findOrFail($apartment_id);
        if ($user_id != $apartment->user_id) {
            return response()->json(['message'=>'Unauthurized'],403);
        }
        if($request->isAccept) {
            $booking->status = 'confirmed';
            $availability = Availability::create([
                'apartment_id'=>$apartment_id,
                'start_non_available_date'=>$booking->start_date,
                'end_non_available_date'=>$booking->end_date,
            ]);
            return response()->json(['message'=> 'The booking has confirmed.','booking'=>$booking]);
        }
        else {
            $booking->status = 'canceled';
            return response()->json(['message'=> 'The booking has canceled.','booking'=>$booking]);
        }


    }

    ///////////////////////////////////////////////////////////////////////////////////////////////

    //للمستاجرين كرمال يشوفو كلشي شقق موجودة
    public function indexAll() {
        $apartments = Apartment::all();
        return response()->json($apartments,200);
    }

    //مشان المستاجر يشوف تفاصيل الشقة
    public function showForTenant(int $id) {
        $apartment = Apartment::findOrFail($id);
        return response()->json($apartment,200);
    }


}
