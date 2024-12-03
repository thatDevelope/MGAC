<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('authToken')->accessToken;

        DB::table('wallets')->insert([
            'user_id' => $user->id,
            'balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $user = \App\Models\User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    // Debug: Check the class of $user
    if (!method_exists($user, 'createToken')) {
        return response()->json([
            'error' => 'The createToken method is not defined on the User model.',
            'user_class' => get_class($user),
            'traits' => class_uses($user),
        ], 500);
    }

    $token = $user->createToken('authToken')->accessToken;

    return response()->json([
        'message' => 'Login successful',
        'token' => $token,
        'user' => $user,
    ]);
}

public function validateToken(Request $request)
{
    // Check if the user is authenticated with a valid token
    return response()->json([
        'message' => 'Token is valid',
        'user' => $request->user(),
    ]);
}

public function refreshToken(Request $request)
{
    // Check if the user is authenticated
    $user = $request->user();

    // Generate a new token for the authenticated user
    $token = $user->createToken('authToken')->accessToken;

    return response()->json([
        'message' => 'Token refreshed successfully',
        'token' => $token,
    ]);
}



}
