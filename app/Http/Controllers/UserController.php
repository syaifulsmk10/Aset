<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
       public function postLogin(Request $request)
    {
        $validate = $request->validate([
            "email" => "required|email",
            "password" => "required",
        ]);
    
        if (!Auth::attempt($validate)) {
            return response()->json([
                'message' => 'Wrong email or password',
                'data' => $validate
            ], 404);
        }
        $user = Auth::user();
        $token = $user->createToken('auth')->plainTextToken;
    
        if ($user->role_id == 1) {
            return response()->json([
                'message' => 'Success Login Admin',
                'data' => $validate,
                'token' => $token
            ], 200);
        }
    
        return response()->json([
            'message' => 'Success Login User',
            'data' => $validate,
            'token' => $token
        ], 200);
    }

    public function registerUser(Request $request)
    {

        $user = user::where('role_id', 1)->get();
        foreach($user as $users){
            if($users->email == $request->email){
            return response()->json([
                "message" => "email is already in use"
            ]);
            }
        }

        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password),
            "role_id" => 1
        ]);

        return response()->json([
            'message' => 'success register admin',
            'data' => $user
        ], 200);
    }

    public function getUser(Request $request)
        {
        $user = user::where('id', Auth::user()->id)->first();
               
        if ($user->role_id == 1) {
        return response()->json([
            'message' => 'success',
            'data' => [
                'foto' => $user->foto,
                'username' => $user->username,
                'email' => $user->email,
                'password' => $user->plaintext_password 

            ]
        ], 200);
    } else{
        return response()->json([
            'message' => 'Failed read',
        ], 400);
    }
    }


    public function update(Request $request){
         $user = user::where('id', Auth::user()->id)->first();

         
        if($user->role_id == 2){
                return response()->json([
                    'message' => "user cant update Profile"
                ]);
        }   

        if($user->role_id == 1){
            if($request->has("username")){
            $user->username = $request->username;
        }

            if($request->has("email")){
            $user->email = $request->email;
        }

            if($request->has("password")){
            $user->password = $request->password;
        }

             $user->save();

      

         if ($request->hasFile('foto')) {



            $Foto = $request->file('foto')->move(public_path(), $request->file('foto')->getClientOriginalName());
            $Photos = $request->file('foto')->getClientOriginalName();



            if(!$Foto){
                return response()->json([
                    "message" => "failed to upload image"
                ]);
            }

            if($user){
                $user->update([
                    "foto" => $Photos,
                ]);
            }
            ;

        }
            
            

   

        
        }
        

        return response()->json([
            "message" => "success update user"
        ]);
    }
}