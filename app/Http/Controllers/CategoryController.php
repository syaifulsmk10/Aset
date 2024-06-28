<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        if (Auth::user()->role->id == 1) {
            $category = Category::all();
            return response()->json([
                "data" => $category
            ]);
        } else {
            return response()->json([
                "message" => "Your login not Admin"
            ]);
        }
    }


    public function create(Request $request)
    {
        if (Auth::user()->role->id == 1) {
            category::create([
                'name' => $request->name,
            ]);
            return response()->json([
                "message" => "success create category"
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
            $category = category::find($id);
            if ($request->has('name')) {
                $category->name = $request->name;
            }

            $category->save();



            return response()->json([
                "message" => "sucess updare category"
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
            $category = category::find($id);
            $category->delete();

            return response()->json([
                "message" => "success delete category"
            ]);
        } else {
            return response()->json([
                "message" => "Your login not Admin"
            ]);
        }
    }
}
