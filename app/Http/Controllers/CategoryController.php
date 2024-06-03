<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(){
        $category = Category::all();
        return response()->json([
            "data" =>  $category
        ]);
    }


    public function create(Request $request){
        category::create([
            $request->name,
        ]);
        return response()->json([
            "message" => "success create category"
        ]);
    }

    public function update(Request $request, $id){
        $category = category::find($id);
    if ($request->has('name')) {
        $category->name = $request->name;
    }
    


        return response()->json([
            "message" => "sucess updae category"
        ]);
    }

    public function delete($id){
        $category = category::find($id);
        $category->delete();

        return response()->json([
            "message" => "success delete category"
        ]);
    }
}
