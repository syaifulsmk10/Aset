<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index()
    {
        $Asset = Asset::all();

        $DataAsset = [];
        foreach ($Asset as $Assets) {
            $DataAsset[] = [
                'asset_code' => $Assets->asset_code,
                'asset_name' => $Assets->asset_name,
                'category' => $Assets->category->name,
                'item_condition' => $Assets->item_condition,
                'price' => $Assets->price,
                'received_date' => $Assets->received_date,
                'expiration_date' => $Assets->expiration_date,
                'image' => $Assets->image
            ];
        }

        return response()->json([
            "data" => $DataAsset
        ]);
    }

    public function create(Request $request)
    {
        $Asset = Asset::create([
            'asset_code' => $request->asset_code,
            'asset_name' => $request->asset_name,
            'category_id' => $request->category_id,
            'item_condition' => $request->item_condition,
            'price' => $request->price,
            'received_date' => $request->received_date,
            'expiration_date' => $request->expiration_date,
            'image' => $request->image,
        ]);

        return response()->json([
            "message" => "sucess add asset"
        ]);
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::find($id);

        if ($request->has('asset_code')) {
        $asset->asset_code = $request->asset_code;
        }
        if ($request->has('asset_name')) {
        $asset->asset_name = $request->asset_name;
        }
        if ($request->has('category_id')) {
        $asset->category_id = $request->category_id;
        }
        if ($request->has('item_condition')) {
        $asset->item_condition = $request->item_condition;
        }
        if ($request->has('price')) {
        $asset->price = $request->price;
        }
        if ($request->has('received_date')) {
        $asset->received_date = $request->received_date;
        }
        if ($request->has('expiration_date')) {
        $asset->expiration_date = $request->expiration_date;
        }
        if ($request->has('image')) {
        $asset->image = $request->image;
        }

        $asset->save();

        return response()->json([
            "message" => "Asset updated successfully"
        ]);
    }

    public function delete($id){
    $asset = Asset::find($id);

     $asset->delete();

    return response()->json([
        "message" => "Asset deleted successfully"
    ]);
}

}
