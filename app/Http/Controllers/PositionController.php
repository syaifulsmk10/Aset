<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PositionController extends Controller
{
    public function index(){
        $position = Position::all();
        return response()->json([
            "data" =>  $position
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
    
        Position::create($request->all());
        return response()->json([
            "message" => "success create position"
        ]);
    }

    public function update(Request $request, $id){
        $position = Position::find($id);
         $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
     
    ]);
     if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation Error',
            'errors' => $validator->errors()
        ], 422);
    }


        if(!$position){
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
    }

    public function delete($id){
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
    }
}
