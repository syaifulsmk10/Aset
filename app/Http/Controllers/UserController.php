<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if (Auth::user()->roles_id == 1) return response()->json([
            'message' => 'admin',
            'data' => $validate,
            'token' => $token
        ], 200);

        if (Auth::user()->roles_id == 2) return response()->json([
            'message' => 'kantin',
            'data' => $validate,
            'token' => $token
        ], 200);

        if (Auth::user()->roles_id == 3) return response()->json([
            'message' => 'bank',
            'data' => $validate,
            'token' => $token
        ], 200);


        return response()->json([
            'message' => 'siswa',
            'data' => $validate,
            'token' => $token
        ], 200);
    }

    public function registerUser(Request $request)
    {
        $user = User::create([
            "name" => $request->name,
            "password" => bcrypt($request->password),
            "roles_id" => 1
        ]);

        return response()->json([
            'message' => 'success register admin',
            'data' => $user
        ], 200);
    }   



    public function getUser(Request $request)
{
    $user = $request->user();

    if ($user->roles_id == 1) {
        $role = 'admin';
    } elseif ($user->roles_id == 2) {
        $role = 'kantin';
    } elseif ($user->roles_id == 3) {
        $role = 'bank';
    } else {
        $role = 'siswa';
    }

    return response()->json([
        'message' => 'success',
        'data' => [
            'name' => $user->name,
            'role' => $role
        ]
    ], 200);
}


   

}
