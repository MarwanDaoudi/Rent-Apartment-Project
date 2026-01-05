<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApartmentRequest;
use App\Http\Requests\UpdateApartmentRequest;
use App\Models\Apartment;
use App\Models\Availability;
use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ApartmentController extends Controller
{
    //
    public function store(StoreApartmentRequest $request)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $validatedData = $request->validated();
        $validatedData['user_id'] = $user_id;
        $apartment = Apartment::create($validatedData);
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('apartments', 'public');

                $apartment->images()->create([
                    'image' => $path
                ]);
            }
        } else {
            $apartment->delete();
            return response()->json(['message' => 'No images provided'], 400);
        }
        return response()->json(['apartment' => $apartment], 200);
    }

    public function update(UpdateApartmentRequest $request, int $id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $apartment = Apartment::findOrFail($id);
        if ($user_id != $apartment->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        $apartment->update($request->validated());

        if ($request->filled('deleted_images')) {
            $imagesToDelete = $apartment->images()
                ->whereIn('id', $request->deleted_images)
                ->get();
            foreach ($imagesToDelete as $image) {
                Storage::disk('public')->delete($image->image);
                $image->delete();
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('apartments', 'public');

                $apartment->images()->create([
                    'image' => $path,
                ]);
            }
        }
        return response()->json(['message' => 'Apartment updated successfully', 'apartment' => $apartment->load('images')], 200);
    }

    //للمؤجر مشان يشوف الشقق يلي عندو ياها
    public function index()
    {
        $user = Auth::user();
        $apartments = $user->apartments()->with('images')->get();
        return response()->json(['apartment' => $apartments], 200);
    }

    //مشان المؤجر يشوف تفاصيل الشقة تبعو
    public function show(int $id)
    {
        $user = Auth::user();
        
        $user_id = $user->id;
        $apartment = Apartment::with('images')->findOrFail($id);
        if ($user_id != $apartment->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        return response()->json(['apartment' => $apartment], 200);
    }

    //مشان صاجب لشقة يحذفها اذا بدو
    public function destroy(int $id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $apartment = Apartment::findOrFail($id);
        if ($user_id != $apartment->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        $apartment->delete();
        return response()->json(['message' => 'The apartment deleted'], 204);
    }

    //####################################################################
    //اذا بدي اعرض للمؤجر كل الحجوزات على شقة معينة الو
    public function showBookingsForApartment(int $id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $apartment = Apartment::findOrFail($id);
        if ($user_id != $apartment->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        return response()->json(['bookings' => $apartment->bookings], 200);
    }

    //اذا بدي اعرض للمؤجر كل الحجوزات الموافق عليها لشقة معينة الو
    public function showConfirmedBookingsForApartment(int $id)
    {
        $user = Auth::user();
        $user_id = $user->id;
        $apartment = Apartment::findOrFail($id);
        if ($user_id != $apartment->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        $bookings = $apartment->bookings()->where('status', 'confirmed')->get();
        return response()->json(['bookings' => $bookings], 200);
    }

    //مشان اعرض للمؤجر كلشي حجوزات عندو ياها للشقق تبعوتو
    public function showAllBookings()
    {
        $user = Auth::user();
        $bookings = $user->apartments->flatMap->bookings;
        return response()->json(['bookings' => $bookings], 200);
    }

    //مشان اعرض للمؤجر كلشي حجوزات موافق عليها
    public function showAllConfirmedBookings()
    {
        $user = Auth::user();

        $bookings = Booking::whereHas('apartment', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('status', 'confirmed')
            ->get();

        return response()->json(['bookings' => $bookings], 200);
    }

    // مشان المؤجر يوافق عالحجز او لا
    public function confirmBooking(Request $request, int $booking_id)
    {
        $booking = Booking::findOrFail($booking_id);

        $apartment_id = $booking->apartment_id;

        $user = Auth::user();
        $user_id = $user->id;
        $apartment = Apartment::findOrFail($apartment_id);
        if ($user_id != $apartment->user_id) {
            return response()->json(['message' => 'Unauthurized'], 403);
        }
        if ($booking->status === 'pending') {
            if ($request->isAccept) {
                $booking->status = 'confirmed';
                $booking->save();
                $tenant = $booking->user;
                $user->increment('balance', $booking->total_cost);
                $tenant->decrement('balance', $booking->total_cost);
                $availability = Availability::create([
                    'apartment_id' => $apartment_id,
                    'start_non_available_date' => $booking->start_date,
                    'end_non_available_date' => $booking->end_date,
                ]);
                $notification = Notification::create([
                    'user_id' => $tenant->id,
                    'message' => 'Your booking for apartment ID ' . $apartment_id . ' has been confirmed.'
                ]);
                // $allBookings = Booking::where('apartment_id', $apartment_id)->get();
                // foreach ($allBookings as $mybooking) {
                //     if (
                //         $mybooking->status === 'pending'
                //         && $mybooking->id !== $booking->id
                //         && (Carbon::parse($mybooking->start_date)->lt(Carbon::parse($booking->end_date))
                //             && Carbon::parse($booking->start_date)->lt(Carbon::parse($mybooking->end_date)))
                //     ) {
                //         $mybooking->status = 'rejected';
                //         $mybooking->save();
                //     }
                // }
                $conflictingBookings = Booking::where('apartment_id', $apartment->id)
                    ->where('id', '!=', $booking->id)
                    ->where('status', 'pending')
                    ->where(function ($query) use ($booking) {
                        $query->where('start_date', '<', $booking->end_date)
                            ->where('end_date', '>', $booking->start_date);
                    })
                    ->get();
                foreach ($conflictingBookings as $conflicting) {
                    $conflicting->status = 'rejected';
                    $conflicting->save();
                    $notification = Notification::create([
                    'user_id' => $conflicting->user_id,
                    'message' => 'Your booking for apartment ID ' . $apartment_id . ' has been rejected.'
                ]);
                }
                return response()->json(['message' => 'The booking has confirmed and conflicting requests has rejected.', 'booking' => $booking]);
            } else {
                $booking->status = 'rejected';
                $booking->save();
                $tenant = $booking->user;
                $notification = Notification::create([
                    'user_id' => $tenant->id,
                    'message' => 'Your booking for apartment ID ' . $apartment_id . ' has been rejected.'
                ]);
                return response()->json(['message' => 'The booking has canceled.', 'booking' => $booking]);
            }
        }
        return response()->json(['message' => 'you have already confirmed/rejected this booking'], 200);
    }



    ///////////////////////////////////////////////////////////////////////////////////////////////

    //للمستاجرين كرمال يشوفو كلشي شقق موجودة
    public function indexAll()
    {
        $apartments = Apartment::with('images')->get();
        return response()->json(['apartments' => $apartments], 200);
    }

    //مشان المستاجر يشوف تفاصيل الشقة
    public function showForTenant(int $id)
    {
        $user = Auth::user();
        $apartment = Apartment::with('images')->findOrFail($id);
        $isFavorite = $user->favorites()->where('apartment_id', $apartment->id)->exists();
        return response()->json(['apartment' => $apartment, 'is_favorite' => $isFavorite], 200);
    }

    // اضافة وازلة من المفضلة
    public function toggleFavorite(Request $request, $apartmentId)
    {
        $user = Auth::user();
        $apartment = Apartment::findOrFail($apartmentId);
        $user->favorites()->toggle($apartment->id);
        return response()->json(['message' => 'Added/Removed from the Favorite'], 200);
    }

    //كلشي مفضلة عندي
    public function getFavorites()
    {
        $user = Auth::user();
        $favorites = $user->favorites()->with('images')->get();
        return response()->json(['Favorites' => $favorites], 200);
    }

    // كرمال الفلترة
    public function filteringApartment(Request $request)
    {
        $apartments = Apartment::with('images')->filter($request->only([
            'city',
            'town',
            'min_price',
            'max_price',
            'min_rooms',
            'max_rooms',
            'min_rating',
            'min_space',
            'max_space',
        ]))->get();

        return response()->json(['apartments' => $apartments], 200);
    }

    public function getLastFiveApartment()
    {
        return Apartment::with('images')->latest()->take(5)->get();
    }
}
