<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    
    public function index(){
        $Department = Department::all();
        return response()->json([
            "data" =>  $Department
        ]);
    }

    

    public function create(Request $request){
         $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
    ]);
     if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);
    }
        Department::create($request->all());
        return response()->json([
            "message" => "success create Department"
        ]);
    }

    public function update(Request $request, $id){

          $validator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
    ]);
     if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);
    }

        $Department = Department::find($id);
        if (!$Department) {
            return response()->json([
                "message" => "Department Not Found"
            ]);
        }

        if ($request->has('name')) {
        $Department->name = $request->name;
        $Department->save();
    }


        return response()->json([
            "message" => "sucess updae Department"
        ]);
    }

    public function delete($id){
        $Department = Department::find($id);

         if (!$Department) {
            return response()->json([
                "message" => "Department Not Found"
            ]);
        }
        $Department->delete();

        return response()->json([
            "message" => "success delete Department"
        ]);
    }
}
