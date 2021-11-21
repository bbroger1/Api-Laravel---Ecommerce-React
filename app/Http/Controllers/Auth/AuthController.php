<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|max:191|string',
            'email'    => 'required|max:191|email|unique:users,email',
            'password' => 'required|min:6|string',
            'passwordConfirm' => 'required|min:6|same:password'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'validation_errors' => $validator->messages(),
            ]);
        }

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password)
        ]);

        $token = $user->createToken($user->email . '_Token')->plainTextToken;

        return response()->json([
            'status'    => 200,
            'username'  => $user->name,
            'token'     => $token,
            'message'   => 'Registered Successfully'
        ]);
    }
}
