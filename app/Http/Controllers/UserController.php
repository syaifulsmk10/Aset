<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserController extends Controller
{
      public function postLogin(Request $request)
    {
        $validate = $request->validate([
            "name" => "required",
            "password" => "required",
        ]);
        $token = User::where("name", $request->name)->first()->createToken('auth')->plainTextToken;

        if (!Auth::attempt($validate)) return response()->json([
            'message' => 'wrong username or password',
            'data' => $validate
        ], 404);

        if (Auth::user()->role_id == 1) return response()->json([
            'message' => 'Berhasil Login Admin',
            'data' => $validate,
            'token' => $token
        ], 200);

        return response()->json([
            'message' => 'Berhasil Login User',
            'data' => $validate,
            'token' => $token
        ], 200);
    }

    public function registerUser(Request $request)
    {
        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => bcrypt($request->password),
            "role_id" => 1
        ]);

        return response()->json([
            'message' => 'success register admin',
            'data' => $user
        ], 200);
    }

    public function getUser(Request $request)
        {
            $user = $request->user();
               
        if ($user->role_id == 1) {
        return response()->json([
            'message' => 'success',
            'data' => [
                'name' => $user->name,
                'role' => 'admin',
            ]
        ], 200);
    } else {

        return response()->json([
            'message' => 'success',
            'data' => [
                'name' => $user->name,
                'role' => 'user'
            ]
        ], 200);
        }
    }


}