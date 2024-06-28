<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PositionController extends Controller
{
    public function index()
    {
        if (Auth::user()->role->id == 1) {
            $position = Position::all();
            return response()->json($position);
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

            Position::create($request->all());
            return response()->json([
                "message" => "success create position"
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
                'name' => 'sometimes|required|string|max:255',

            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }
            $position = Position::find($id);


            if (!$position) {
                return response()->json([
                    "message" => "Position Not Found"
                ]);
            }
            if ($request->has('name')) {
                $position->name = $request->name;
                $position->save();
            }


            return response()->json([
                "message" => "sucess updae position"
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
            $position = position::find($id);
            if (!$position) {
                return response()->json([
                    "message" => "Position Not Found"
                ]);
            }
            $position->delete();

            return response()->json([
                "message" => "success delete position"
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
            $position = Position::find($id);

            if (!$position) {
                return response()->json([
                    "message" => "Position Not Found"
                ]);
            }

            return response()->json($position);
        } else {
            return response()->json([
                "message" => "Your login not Admin"
            ]);
        }
    }
}
