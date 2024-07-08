<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{

    public function index(Request $request)
    {
        if (Auth::user()->role->id == 1) {
            $query = User::where('role_id', 2);

            if (!$query) {
                return response()->json([
                    "message" => "not employees",
                ]);
            }

            foreach ($query as $users) {
                if ($users->email == $request->email) {
                    return response()->json([
                        "message" => "email is already in use"
                    ]);
                }
            }


            $search = $request->input('search');
            $query->whereHas('Employee', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('nip', 'LIKE', "%{$search}%");
            });


            $perpage = $request->input('per_page', 10);
            $users = $query->paginate($perpage);

            // $user_data = collect($users)->map(function($user){
            //         return  [
            //         "name" => $user->name,
            //         "nip" => $user->Employee->nip,
            //         "email" => $user->email,
            //         "department" => $user->Employee->department->name,
            //         "position" => $user->Employee->position->name
            //         ];
            // })->all();
            foreach ($users as $user) {
                $userData[] = [
                    "id" => $user->id,
                    "name" => $user->name,
                    "nip" => $user->Employee->nip,
                    "email" => $user->email,
                    "department" => $user->Employee->department->name,
                    "position" => $user->Employee->position->name
                ];
            }
            return response()->json($users);
        } else {
            return response()->json([
                "message" => "Your login not admin"
            ]);
        }
    }



    public function create(Request $request)
    {
        if (Auth::user()->role->id == 1) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'nip' => 'required|integer|unique:employees',
                'department_id' => 'required|exists:departments,id',
                'position_id' => 'required|exists:positions,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $user = User::create([
                "name" => $request->name,
                "email" => $request->email,
                "password" => Hash::make($request->password),
                "role_id" => 2
            ]);

            $employes = Employee::create([
                'user_id' => $user->id,
                'nip' => $request->nip,
                'department_id' => $request->department_id,
                'position_id' => $request->position_id,

            ]);

            return response()->json([
                "message" => "successs create employe"
            ]);
        } else {
            return response()->json([
                "message" => "Your login not admin"
            ]);
        }
    }


    public function update(Request $request, $id)
    {

        if (Auth::user()->role->id == 1) {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users',
                'password' => 'sometimes|required|string|min:8',
                'nip' => 'sometimes|required|integer|unique:employees',
                'department_id' => 'sometimes|required|exists:departments,id',
                'position_id' => 'sometimes|required|exists:positions,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    "message" => "user not found"
                ]);
            }

            if ($request->has('name')) {
                $user->name = $request->name;
            }
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            $employee = Employee::where('user_id', $user->id)->first();

            if ($request->has('nip')) {
                $employee->nip = $request->nip;
            }
            if ($request->has('department_id')) {
                $employee->department_id = $request->department_id;
            }
            if ($request->has('position_id')) {
                $employee->position_id = $request->position_id;
            }

            $employee->save();

            return response()->json([
                'message' => 'Success update employee'
            ]);
        } else {
            return response()->json([
                "message" => "Your login not admin"
            ]);
        }
    }


    public function delete($id)
    {
        if (Auth::user()->role->id == 1) {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    "message" => "user not found"
                ]);
            }
            $user->delete();
            $employee = Employee::where('user_id', $user->id)->first();
            if ($employee) {
                $employee->delete();
            }


            return response()->json([
                'message' => 'Success delete employee'
            ]);
        } else {
            return response()->json([
                "message" => "Your login not admin"
            ]);
        }
    }


    //     public function search(Request $request){
    //         $query = $request->input("query");


    //         $employees = Employee::where('nip', 'like', "%$query%")
    //             ->orwhereHas('user', function ($q) use ($query) {
    //                 $q->where('name', 'like', "%{$query}%")
    //                   ->orWhere('email', 'like', "%{$query}%");
    //             })
    //             ->orWhereHas('department', function ($q) use ($query) {
    //                 $q->where('name', 'like', "%{$query}%");
    //             })
    //             ->orWhereHas('position', function ($q) use ($query) {
    //                 $q->where('name', 'like', "%{$query}%");
    //             })
    //             ->with(['user:id,name,email', 'department:id,name', 'position:id,name'])
    //             ->get();



    //      foreach($employees as $employes){
    //             $result[] = [
    //                 'employee_id' => $employes->id,
    //                 'nip' => $employes->nip,
    //                 'department' => $employes->department->name,
    //                 'position' => $employes->position->name,
    //                 'user' => [
    //                     'name' => $employes->user->name,
    //                     'email' => $employes->user->email,]
    //             ];
    //         }


    //         return response()->json($result);
    // }




    public function detail($id)
    {
        if (Auth::user()->role->id == 1) {
            $Employee = User::with(['employee.department', 'employee.position'])->where('role_id', 2)->first();
            if (!$Employee) {
                return response()->json([
                    "message" => "Emplployee not found"
                ]);
            }


            return response()->json($Employee);
        } else {
            return response()->json([
                "message" => "Your login not admin"
            ]);
        }

    }
}
