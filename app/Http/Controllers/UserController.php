<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\TemporaryUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //
    
    function register(StoreUserRequest $request) {
        $validatedData = $request->validated();
        $phone = User::where('phone',$request->phone)->first();
        if ($phone) {
            return response()->json(['message'=>'the phone is already exist'],200);
        }
        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image')->store('users/profiles','public');
            $validatedData['profile_image'] = $image;
        }

        if ($request->hasFile('id_image')) {
            $image = $request->file('id_image')->store('users/identities','public');
            $validatedData['id_image'] = $image;
        }

        $validatedData['password'] =Hash::make($request->password);
        $user = TemporaryUser::create($validatedData);
        return response()->json(['message'=>'User Registered Successfully, We will contact you soon', $user], 201);
    }

    function temporaryIndex() {
        $tempUsers = TemporaryUser::all();
        return response()->json($tempUsers,200);
    }

    function acceptUser(Request $request, int $id) {
        $user = TemporaryUser::findOrFail($id);
        if ($request->isAccept) {
            User::create($user->toArray());
            $user->delete();
            return response()->json(['message'=>'User has been accepted', 'User'=> $user],200);
        }
        else {
            $user->delete();
            return response()->json(['message'=>'User rejected'],204);
        }
    }

    function login(Request $request) {
        $request->validate([
            'phone'=>'required|digits:8|string',
            'password'=>'required|string|min:8',
        ]);
        $phone = TemporaryUser::where('phone',$request->phone)->first();
        if ($phone) {
            return response()->json(['message'=>'the admin hasn\'t accepted you yet, please try again later'],404);
        }
        if (!Auth::attempt($request->only('phone','password'))) {
            return response()->json(['message'=>'Unauthurized'],401);
        }
        $user = User::where('phone',$request->phone)->first();
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['message'=>'User Login Successfully','user'=>$user,'token'=> $token],200);
    }
    
    function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message'=>'User Logout Successfully'], 200);
    }

    function index() {
        $users = User::all();
        return response()->json(['users'=>$users],200);
    }

    function destroy(int $id) {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message'=>'The user has been deleted'],204);
    }

    function addBalance(int $id, Request $request) {
        $user = User::findOrFail($id);
        $user->balance += $request->amount;
        $user->save();
        return response()->json(['message'=>'the balance increase successfully.','user'=>$user]);
    }

    function show(int $id) {
        $user = User::findOrFail($id);
        return response()->json(['user'=>$user],200);
    }

}
