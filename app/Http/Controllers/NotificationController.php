<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    //
    function index()
    {
        $user = Auth::user();
        $notifications = $user->notification()->latest()->get();
        return response()->json(['notifications' => $notifications], 200);
    }

    function destroy()
    {
        $user = Auth::user();
        if ($user->notification()->count() === 0) {
        return response()->json(['message' => 'No notifications to delete.'], 200);
        }
        $user->notification()->delete();
        return response()->json(['message' => 'All notifications have been deleted.'], 200);
    }
}
