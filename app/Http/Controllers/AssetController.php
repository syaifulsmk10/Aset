<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Category;
use Illuminate\Http\Request;

class AssetController extends Controller
{public function index(Request $request)
{
   
    
    $query = Asset::query();


   
    if(!$query){
            return response()->json([
                "message" => "asset null"
            ]);
       }

    if ($request->has('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('asset_code', 'LIKE', "%{$search}%")
              ->orWhere('asset_name', 'LIKE', "%{$search}%")
              ->orWhereHas('category', function($q) use ($search) {
                  $q->where('name', 'LIKE', "%{$search}%");
              });
        });
    }

     if ($request->has('status')) {
        $status = $request->input('status');
        $query->where('status', $status);
    }

    if ($request->has('start_date') && $request->has('end_date')) {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query->whereBetween('received_date', [$startDate, $endDate]);
    }

    
    // Pagination
    $perPage = $request->input('per_page', 10); // Default items per page is 10
    $assets = $query->paginate($perPage);

    $dataAsset = [];
    foreach ($assets as $asset) {
        $dataAsset[] = [
            'asset_code' => $asset->asset_code,
            'asset_name' => $asset->asset_name,
            'category' => $asset->category->name,
            'item_condition' => $asset->item_condition,
            'price' => $asset->price,
            'received_date' => $asset->received_date,
            'expiration_date' => $asset->expiration_date,
            'status' => $asset->status,
            'image' => $asset->image
        ];
    }

    return response()->json([
        "data" => $dataAsset,
        "pagination" => [
            'total' => $assets->total(),
            'per_page' => $assets->perPage(),
            'current_page' => $assets->currentPage(),
            'last_page' => $assets->lastPage(),
            'next_page_url' => $assets->nextPageUrl(),
            'prev_page_url' => $assets->previousPageUrl()
        ]
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
            'status' => $request->status,
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
        if ($request->has('status')) {
        $asset->status = $request->status;
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
