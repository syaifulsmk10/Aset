<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ImageAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::with('imageAssets', 'category');

        if (!$query) {
            return response()->json([
                "message" => "asset null"
            ]);
        }

        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('asset_code', 'LIKE', "%{$search}%")
                ->orWhere('asset_name', 'LIKE', "%{$search}%")
                ->orWhereHas('category', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });
        });

        if ($request->has('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query->whereBetween('received_date', [$startDate, $endDate]);
        }

        $perPage = $request->input('per_page', 10); // Default items per page is 10
        $assets = $query->paginate($perPage);

        $assets->getCollection()->transform(function ($asset) {

            return [
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'category' => $asset->category->name,
                'item_condition' => $asset->item_condition,
                'price' => $asset->price,
                'received_date' => $asset->received_date,
                'expiration_date' => $asset->expiration_date,
                'status' => $asset->status,
                'image' => $asset->imageAssets->map(function ($imageAsset) {
                    $data = json_decode($imageAsset->path, true);

                    return array_values(
                        array_map(fn ($path) => env('APP_URL') . 'uploads/assets/' . $path, $data) 
                    );
                })->flatten(1)->all()
            ];
        });

        return response()->json($assets);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_code' => 'required|string|max:10',
            'asset_name' => 'required|string|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'item_condition' => 'required|integer|max:7',
            'price' => 'required|numeric',
            'received_date' => 'required|date',
            'expiration_date' => 'required|date',
            'status' => 'required|integer|max:6',
            'path' => 'required|array|min:1',
            'path.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi untuk setiap file path
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $Asset = Asset::create([
            'asset_code' => $request->asset_code,
            'asset_name' => $request->asset_name,
            'category_id' => $request->category_id,
            'item_condition' => $request->item_condition,
            'price' => $request->price,
            'received_date' => $request->received_date,
            'expiration_date' => $request->expiration_date,
            'status' => $request->status,
        ]);
        if ($request->hasFile('path')) {
            $images = $request->file('path');
            $imagePaths = [];

            foreach ($images as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/assets'), $imageName);
                $imagePaths[] = $imageName;
            }

            ImageAsset::create([
                'asset_id' => $Asset->id,
                'path' => json_encode($imagePaths),
            ]);

            return response()->json([
                "message" => "Success Add Asset"
            ], 200);
        } else {
            return response()->json(['error' => 'No file found'], 400);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'asset_code' => 'sometimes|required|string|max:10',
            'asset_name' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'item_condition' => 'sometimes|required|integer|max:7',
            'price' => 'sometimes|required|numeric',
            'received_date' => 'sometimes|required|date',
            'expiration_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|integer|max:6',
            'path' => 'sometimes|required|array|min:1',
            'path.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json([
                "message" => "asset not found"
            ]);
        }

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

        $asset->save();

        if ($request->hasFile('path')) {
            $images = $request->file('path');
            $imagePaths = [];

            $oldImages = ImageAsset::where('asset_id', $asset->id)->first();
            if ($oldImages) {
                $oldImagePaths = json_decode($oldImages->path, true);
                foreach ($oldImagePaths as $oldImagePath) {
                    $oldImageFullPath = public_path('uploads/assets/' . $oldImagePath);
                    if (file_exists($oldImageFullPath)) {
                        unlink($oldImageFullPath);
                    }
                }
                $oldImages->delete();
            }

            foreach ($images as $image) {
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/assets'), $imageName);
                $imagePaths[] = $imageName;
            }

            ImageAsset::create([
                'asset_id' => $asset->id,
                'path' => json_encode($imagePaths),
            ]);
        }
        return response()->json([
            "message" => "Success Update Asset"
        ], 200);
    }

    public function delete($id)
    {
        $asset = Asset::find($id);
        if (!$asset) {
            return response()->json([
                "message" => "asset not found"
            ]);
        }

        $asset->delete();
        $ImageAsset = ImageAsset::where('asset_id', $asset->id)->first();
        if ($ImageAsset) {
            $ImageAsset->delete();
        }

        return response()->json([
            'message' => 'Success delete Asset'
        ]);
    }

    public function detail($id)
    {
        $asset = Asset::with('category', 'imageAssets')->find($id)->makeHidden('category_id');

        if (!$asset) {
            return response()->json([
                "message" => "asset not found"
            ]);
        }
        $images = [];
        foreach ($asset->imageAssets as $image) {
            $images[] = $image->path;
        }

        $dataApplicant = [
            'id' => $asset->id,
            'asset_code' => $asset->asset_code,
            'asset_name' => $asset->asset_name,
            'category' => $asset->category->name,
            'item_condition' => $asset->item_condition,
            'price' => $asset->price,
            'received_date' => $asset->received_date,
            'expiration_date' => $asset->expiration_date,
            'status' => $asset->status,
            'image' => $asset->imageAssets->map(function ($imageAsset) {
                $data = json_decode($imageAsset->path, true);

                return array_values(
                    array_map(fn ($path) => $path, $data)
                );
            })->flatten(1)->all()
        ];

        return response()->json([
            "dataApplicant" => $dataApplicant
        ]);
    }
}
