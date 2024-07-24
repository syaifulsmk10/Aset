<?php

namespace App\Http\Controllers;

use App\Enums\ItemCondition;
use App\Enums\Status;
use App\Models\Applicant;
use App\Models\Asset;
use App\Models\Category;
use App\Models\ImageAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function index(Request $request)
    {

        if (Auth::user()->role->id == 1) {
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
                if (is_array($status)) {
                    $query->whereIn('status', $status);
                } else {
                    $query->where('status', $status);
                }
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');

                $query->whereBetween('received_date', [$startDate, $endDate]);
            }

            $perPage = $request->input('per_page', 10); // Default items per page is 10
            $assets = $query->paginate($perPage);
            $totalpage = $assets->lastPage();

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
                            array_map(fn($path) => env('APP_URL') . 'uploads/assets/' . $path, $data)
                        );
                    })->flatten(1)->all()
                ];
            });

            return response()->json([
                "data" =>$assets,
                "total_page" => $totalpage
            ]);
        }else{
            return response()->json([
                "message" => "Your login not admin"
            ]);
        }
    }

    public function create(Request $request)
    {

        if (Auth::user()->role->id == 1) {
            $validator = Validator::make($request->all(), [
                'asset_code' => 'required|string|max:5',
                'asset_name' => 'required|string|max:255',
                'category_id' => 'required|integer|exists:categories,id',
                'item_condition' => 'required|integer|max:7',
                'price' => 'required|numeric',
                'received_date' => 'required|date',
                'expiration_date' => 'required|date',
                'status' => 'required|integer|max:8',
                'path' => 'required|array|min:1',
                'path.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi untuk setiap file path
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $category = Category::find($request->category_id);
 

            $Asset = Asset::create([
                'asset_code' => strtoupper(substr($category->name, 0, 1)) . - $request->asset_code,
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
                    $imageName = 'VA' . Str::random(40) . $image->getClientOriginalName();
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

        }else{
            return response()->json([
                "message" => "Your login not admin"
            ]); 
        }
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role->id == 1) {
            $validator = Validator::make($request->all(), [
                // Validasi lainnya...
                'asset_code' => 'sometimes|required|string|max:10',
                'asset_name' => 'sometimes|required|string|max:255',
                'category_id' => 'sometimes|required|integer|exists:categories,id',
                'item_condition' => 'sometimes|required|string|in:Baik,Perlu_Perbaikan,Rusak,Dalam_Perbaikan,Tidak_Aktif,Hilang,Tidak_Layak_Pakai', // Validasi nilai enum sebagai string
                'price' => 'sometimes|required|numeric',
                'received_date' => 'sometimes|required|date',
                'expiration_date' => 'sometimes|required|date',
                'status' => 'sometimes|required|string|in:Aktif,Tidak_Aktif,Dipinjamkan,Dalam_Pemeliharaan,Dalam_Penyimpanan,Dalam_Perbaikan,Dalam_Proses_Peminjaman,Tidak_Layak_Pakai', // Validasi nilai enum status sebagai string
                'path' => 'sometimes|required|array|min:1',
                'path.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $asset = Asset::find($id);
            if (!$asset) {
                return response()->json(["message" => "Asset not found"], 404);
            }

            // Perbarui field lainnya...
            $asset->save();

            if ($request->hasFile('path')) {
                $images = $request->file('path');
                $imagePaths = [];

                // Ambil jalur gambar lama dari database
                $oldImages = ImageAsset::where('asset_id', $asset->id)->first();
                if ($oldImages) {
                    $oldImagePaths = json_decode($oldImages->path, true);

                    // Ganti gambar yang lama dengan yang baru
                    foreach ($images as $index => $image) {
                        $imageName = 'VA' . Str::random(40) . $image->getClientOriginalName();
                        $image->move(public_path('uploads/assets'), $imageName);
                        $imagePaths[] = $imageName;

                        // Hapus gambar lama yang diganti
                        if (isset($oldImagePaths[$index])) {
                            $oldImageFullPath = public_path('uploads/assets/' . $oldImagePaths[$index]);
                            if (file_exists($oldImageFullPath)) {
                                unlink($oldImageFullPath); // Hapus file fisik
                            }
                        }
                    }

                    // Gabungkan jalur gambar baru dan lama yang tidak diganti
                    $imagePaths = array_merge($imagePaths, array_slice($oldImagePaths, count($imagePaths)));
                    $oldImages->delete(); // Hapus entri database lama
                } else {
                    // Jika tidak ada gambar lama, hanya tambahkan gambar baru
                    foreach ($images as $image) {
                        $imageName = 'VA' . Str::random(40) . $image->getClientOriginalName();
                        $image->move(public_path('uploads/assets'), $imageName);
                        $imagePaths[] = $imageName;
                    }
                }

                ImageAsset::create([
                    'asset_id' => $asset->id,
                    'path' => json_encode($imagePaths),
                ]);
            }

            return response()->json(["message" => "Success Update Asset"], 200);
        } else {
            return response()->json(["message" => "Your login not admin"], 403);
        }
    }


    public function delete($id)
    {
        if (Auth::user()->role->id == 1) {
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
        }else{
            return response()->json([
                "message" => "Your login not admin"
            ]); 
        }
    }

    public function detail($id)
    {
        // Temukan asset berdasarkan ID
        $asset = Asset::find($id);

        if (!$asset) {
            return response()->json(['error' => 'Asset not found'], 404);
        }

        // Ambil informasi gambar terkait dengan asset
        $imageAsset = ImageAsset::where('asset_id', $asset->id)->first();

        $imagePaths = [];
        if ($imageAsset) {
            $imagePaths = json_decode($imageAsset->path, true);
        }

        // Buat respons dengan detail asset dan informasi gambar
        $response = [
     
                'id' => $asset->id,
                'asset_code' => $asset->asset_code,
                'asset_name' => $asset->asset_name,
                'category_id' => $asset->category_id,
                'item_condition' => $asset->item_condition,
                'price' => $asset->price,
                'received_date' => $asset->received_date,
                'expiration_date' => $asset->expiration_date,
                'status' => $asset->status,
       
            'image_assets' => []
        ];

        foreach ($imagePaths as $path) {
            $response['image_assets'][] = [
                'asset_id' => $asset->id,
                'path' => $path,
            ];
        }

        return response()->json($response, 200);
    }


    public function destroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:assets,id',
        ]);

      
        Asset::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Aset yang dipilih berhasil dihapus.']);
    }
}
