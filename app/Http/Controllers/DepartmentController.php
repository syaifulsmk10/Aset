<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(){
        $department = Department::all();
        return response()->json([
            "data" =>  $department
        ]);
    }


    public function create(Request $request){
        department::create([
            $request->name,
        ]);
        return response()->json([
            "message" => "success create department"
        ]);
    }

    public function update(Request $request, $id){
        $department = department::find($id);
        $department->update();


        return response()->json([
            "message" => "sucess updae department"
        ]);
    }

    public function delete($id){
        $department = department::find($id);
        $department->delete();

        return response()->json([
            "message" => "success delete department"
        ]);
    }
}
