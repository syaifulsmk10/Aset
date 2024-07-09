<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{

    public function index()
    {
        if (Auth::user()->role->id == 1 || Auth::user()->role->id == 2) {
            $Department = Department::all();
            return response()->json($Department);
        } else {
            return response()->json([
                "message" => "Your login not Admin"
            ]);
        }
    }




    public function create(Request $request)
    {

        if (Auth::user()->role->id == 1) {
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
        } else {
            return response()->json([
                "message" => "Your login not Admin"
            ]);
        }

    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role->id == 1) {
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
        } else {
            return response()->json([
                "message" => "Your login not Admin"
            ]);
        }
    }

    public function delete($id)
    {
        if (Auth::user()->role->id == 1) {
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
        } else {
            return response()->json([
                "message" => "Your login not Admin"
            ]);
        }
    }

    public function detail($id)
    {
        if (Auth::user()->role->id == 1) {
            $department = Department::find($id);

            if (!$department) {
                return response()->json([
                    "message" => "Department Not Found"
                ]);
            }

            return response()->json($department);
        } else {
            return response()->json([
                "message" => "Your login not Admin"
            ]);
        }
    }
}
