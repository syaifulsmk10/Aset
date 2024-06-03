<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(){
        $position = Position::all();
        return response()->json([
            "data" =>  $position
        ]);
    }


    public function create(Request $request){
        position::create([
            $request->name,
        ]);
        return response()->json([
            "message" => "success create position"
        ]);
    }

    public function update(Request $request, $id){
        $position = position::find($id);
        if ($request->has('name')) {
        $position->name = $request->name;
    }


        return response()->json([
            "message" => "sucess updae position"
        ]);
    }

    public function delete($id){
        $position = position::find($id);
        $position->delete();

        return response()->json([
            "message" => "success delete position"
        ]);
    }
}
