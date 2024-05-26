<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
  public function index(){
    // Fetch users with role_id 2
    $users = User::where('role_id', 2)->get();

    $userData = [];

    // Iterate through each user and gather necessary information
    foreach($users as $user){
        $userData[] = [
            "name" => $user->name,
            "nip" => $user->Employee->nip,
            "email" => $user->email,
            "department" => $user->Employee->department->name,
            "position" => $user->Employee->position->name
        ];
    }

    // Return response as JSON
    return response()->json([
        'users' => $userData
    ]);
}


    public function create(Request $request){
        $user = user::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => "aaaa",
            'role_id' => 2,
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

    }


    public function update(Request $request, $id){
    $user = User::find($id);

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

    }


//     public function delete($id){
//     $user = User::find($id);
//       $user->delete();
//          $employee = Employee::where('user_id', $user->id)->first();
//             if ($employee) {
//                  $employee->delete();
//             }

  

//       return response()->json([
//         'message' => 'Success delete employee'
//     ]);
// }


    public function search(Request $request){
        $query = $request->input("query");


        $employees = Employee::where('nip', 'like', "%$query%")
            ->orwhereHas('user', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->orWhereHas('department', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->orWhereHas('position', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%");
            })
            ->with(['user:id,name,email', 'department:id,name', 'position:id,name'])
            ->get();

          
        foreach($employees as $employes){
            $result[] = [
                'employee_id' => $employes->id,
                'nip' => $employes->nip,
                'department' => $employes->department->name,
                'position' => $employes->position->name,
                'user' => [
                    'name' => $employes->user->name,
                    'email' => $employes->user->email,]
            ];
        }


        return response()->json($result);

    }
    

   
}
