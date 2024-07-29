<?php

namespace App\Http\Controllers;

use App\Enums\Type;
use App\Models\Applicant;
use App\Models\Asset;
use App\Models\Image;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApplicantController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        if (Auth::user()->role->id == 2) {
            $query = Applicant::with(['asset.category', 'user'])->where('user_id', Auth::user()->id)->whereNull('delete_user');

            //search
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->orWhereHas('asset', function ($q) use ($search) {
                    $q->where('asset_name', 'LIKE', "%{$search}%")
                        ->orWhereHas('category', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%{$search}%");
                        });
                });
            });

            //Date
            if ($request->has("start_date") && $request->has('end_date')) {
                $startdate = $request->input("start_date");
                $enddate = $request->input("end_date");
                $query->whereBetween('submission_date', [$startdate, $enddate]);
            }

            //status
            if ($request->has('status')) {
                $status = $request->input('status');
                if (is_array($status)) {
                    $query->whereIn('status', $status);
                } else {
                    $query->where('status', $status);
                }
            }

            //pagination
            $perpage = $request->input("per_page", 10);
            $applicants = $query->paginate($perpage);
            $totalpage = $applicants->lastPage();

            return response()->json([
                "data" => $applicants,
                "total_page" => $totalpage
            ]);
        } else {
            return response()->json([
                "message" => "Your login not User"
            ]);
        }
    }


    public function getaset(Request $request)
    {
        $transactionType = $request->query('type');
        $userId = Auth::id();

        if ($transactionType === '1') {
            $assets = Asset::whereIn('status', [1, 7]) // Status "Aktif"
                ->where('item_condition', 1)
                ->get();
        } elseif ($transactionType === '2') {
            $assets = Asset::whereIn('status', [3, 9])
                ->whereHas('applicants', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->whereNotNull('accepted_at')
                    ->where('type', 1)
                    ->where('updated_at', 'desc');
                }) // Status "Dipinjamkan"
                ->get();
        } else {
            return response()->json(['message' => 'Invalid transaction type'], 400);
        }

        if ($assets->isEmpty()) {
            return response()->json([
                "message" => "asset not found"
            ]);
        }

        $dataasset = [];
        foreach ($assets as $asset) {
            $dataasset[] = [
                "id" => $asset->id,
                "status" => $asset->status,
                "asset_name" => $asset->asset_name
            ];
        }

        return response()->json($dataasset);
    }



    public function create(Request $request)
    {
        if (Auth::user()->role->id == 2) {


            $asset = Asset::find($request->asset_id);
            if ($asset) {
                if (($asset->status == 'Aktif' || $asset->status == 'Dalam_Proses_Peminjaman') && $request->type == 1 && $asset->item_condition == "Baik") {
                    $validator = Validator::make($request->all(), [
                        'asset_id' => 'required|exists:assets,id',
                        'submission_date' => 'sometimes|required|date|after_or_equal:today',
                        'expiry_date' => 'required|date|after:submission_date',
                        'type' => 'required|in:1,2',
                        'path' => 'required|array|min:1',
                        'path.*' => 'required||image|mimes:jpeg,png,jpg,gif|max:2048',
                    ]);

                    if ($validator->fails()) {
                        return response()->json(['error' => $validator->errors()], 400);
                    }
                    $applicant = Applicant::create([
                        'user_id' => Auth::user()->id,
                        'asset_id' => $request->asset_id,
                        'submission_date' => $request->submission_date,
                        'expiry_date' => $request->expiry_date,
                        'type' => $request->type,
                        'status' => 1,
                    ]);


                    if ($request->hasFile('path')) {
                        $images = $request->file('path');
                        $imagePaths = [];

                        foreach ($images as $image) {
                            $imageName = 'VA' . Str::random(40) . $image->getClientOriginalName();
                            $image->move(public_path('uploads/applicant'), $imageName);
                            $imagePaths[] = $imageName;
                        }

                        Image::create([
                            'applicant_id' => $applicant->id,
                            'path' => json_encode($imagePaths),
                        ]);

                        $asset->status = 7;
                        $asset->save();


                        return response()->json([
                            "message" => "Success Add Applicant"
                        ], 200);
                    } else {
                        return response()->json([
                            "message" => "Foto not found",
                        ], 200);
                    }
                } elseif ($asset->status == 'Dipinjamkan' && $request->type == 2) {

                    $applicant = Applicant::where('asset_id', $request->asset_id)->where('type', 1)->whereNotNull('accepted_at') ->orderBy('updated_at', 'desc')->first();

                    if ($applicant->user_id != Auth::user()->id) {
                        return response()->json([
                            'message' => 'Anda bukan peminjam dari aset ini.'
                        ], 403);
                    }

                    $validator = Validator::make($request->all(),    [
                        'asset_id' => 'required|exists:assets,id',
                        'type' => 'required|in:1,2',
                        'submission_date' => 'sometimes|required|date|after_or_equal:today',
                        'expiry_date' => 'nullable|date|after:submission_date',
                        'path' => 'required|array|min:1',
                        'path.*' => 'required||image|mimes:jpeg,png,jpg,gif|max:2048',
                    ]);

                    if ($validator->fails()) {
                        return response()->json(['error' => $validator->errors()], 400);
                    }
                    $applicant = Applicant::create([
                        'user_id' => Auth::user()->id,
                        'asset_id' => $request->asset_id,
                        'submission_date' => $request->submission_date,
                        'expiry_date' => $request->expiry_date,
                        'type' => $request->type,
                        'status' => 1,
                    ]);

                    if ($request->hasFile('path')) {
                        $images = $request->file('path');
                        $imagePaths = [];

                        foreach ($images as $image) {
                            $imageName = 'VA' . Str::random(40) . $image->getClientOriginalName();
                            $image->move(public_path('uploads/applicant'), $imageName);
                            $imagePaths[] = $imageName;
                        }

                        Image::create([
                            'applicant_id' => $applicant->id,
                            'path' => json_encode($imagePaths),
                        ]);
                        $asset->status = 9;
                        $asset->save();

                        return response()->json([
                            "message" => "Success Add Applicant"
                        ], 200);
                    }

                    return response()->json([
                        'message' => 'Success Applicant'
                    ]);
                } else {
                    return response()->json([
                        'message' => 'The condition of the asset does not meet the requirements'
                    ], 400);
                }
            } else {
                return response()->json([
                    'message' => 'Asset not found.'
                ], 404);
            }
        } else {
            return response()->json([
                "message" => "Your login not User"
            ]);
        }
    }

    public function delete($id)
    {
        if (Auth::user()->role->id == 2) {
            $Applicant = Applicant::where('id', $id)->where('user_id', Auth::user()->id)->first();
            if (!$Applicant || $Applicant->delete_user != null) {
                return response()->json([
                    'message' => 'Applicant not found.'
                ], 404);
            }
            $asset = Asset::find($Applicant->asset_id);
            $image = Image::where('applicant_id', $Applicant->id)->first();
            if ($Applicant->status == "Disetujui" || $Applicant->status == "Ditolak") {
                $Applicant->update([
                    "delete_user" => now(),
                ]);
                return response()->json([
                    "message" =>  "success delete",
                ]);
            } elseif ($Applicant && $Applicant->status == "Belum_Disetujui" && $Applicant->type == "Peminjaman") {
                $Applicant->delete();
                $image->delete();
                $asset->update([
                    'status' => 1,
                ]);
                return response()->json([
                    "message" =>  "success delete",
                ]);
            } elseif ($Applicant && $Applicant->type == "Pengembalian" && $Applicant->status == "Belum_Disetujui") {
                $Applicant->delete();
                $image->delete();
                $asset->update([
                    'status' => 3,
                ]);


                return response()->json([
                    "message" =>  "success delete",
                ]);
            } else {
                return response()->json([
                    "message" => "cant delete",
                ]);
            }
        } else {
            return response()->json([
                "message" => "Your login not User"
            ]);
        }
    }


    public function detail($id)
    {
        if (Auth::user()->role->id == 2) {
            $Applicant = Applicant::with(['asset', 'user', 'images'])->where('user_id', Auth::user()->id)->find($id);
            if (!$Applicant) {
                return response()->json([
                    'message' => 'Applicant not found.'
                ], 404);
            }

            $images = [];
            foreach ($Applicant->images as $image) {
                $images[] = $image->path;
            }

            $dataApplicant = [
                "id" => $Applicant->id,
                "idAsset" => $Applicant->asset->id,
                "NameAsset" => $Applicant->asset->asset_name,
                "idCategory" =>
                $Applicant->asset->category->id,
                "Category" => $Applicant->asset->category->name,
                "SubmissionDate" => $Applicant->submission_date,
                "ExpiryDate" => $Applicant->expiry_date,
                "UserId" => $Applicant->user->id,
                "UserApplicants" => $Applicant->user->name,
                "type" => $Applicant->type,
                "Images" => $Applicant->images->map(function ($image) {
                    $data = json_decode($image->path, true);

                    return array_values(
                        array_map(fn ($path) => env('APP_URL') . 'uploads/applicant/' . $path, $data)
                    );
                })->flatten(1)->all() // Kumpulkan URL gambar dalam array
            ];

            return response()->json($dataApplicant);
        } else {
            return response()->json([
                "message" => "Your login not User"
            ]);
        }
    }


    public function update(Request $request, $id)
    {

        if (Auth::user()->role->id == 2) {
            $Applicant = Applicant::where('id', $id)->where('user_id', Auth::user()->id)->first();
            $validator = Validator::make($request->all(), [
                'asset_id' => 'sometimes|required|exists:assets,id',
                'submission_date' => 'sometimes|required|date|after_or_equal:today',
                'expiry_date' => 'sometimes|required|date|after:submission_date',
                'type' => 'sometimes|required|in:1,2',
                'path.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }


            if (!$Applicant) {
                return response()->json([
                    'message' => 'Applicant not found.'
                ], 404);
            }
            $oldAsset = Asset::find($Applicant->asset_id);

            // if ($request->has('type')) {
            //     $Applicant->type = Type::getValue($request->type);
            // }

            if ($Applicant && $Applicant->status == "Belum_Disetujui" && $oldAsset->status == 'Dalam_Proses_Peminjaman') {

                if ($request->has('type') && $request->type != 1) {
                    return response()->json(['error' => 'Type ID cannot be changed.'], 400);
                }

                if ($request->has('asset_id')) {
                    $newAsset = Asset::where('id', $request->asset_id)->where('item_condition', '1')->wherein('status', [1, 7])->first();
                    if (!$newAsset) {
                        return response()->json(['message' => 'item_Condition'], 400);
                    };
                    if ($newAsset && $Applicant->asset_id == $request->asset_id) {
                        $newAsset->status = 7;
                        $newAsset->save();
                    };

                    if ($newAsset  && $Applicant->asset_id != $request->asset_id) {

                        $newAsset->status = 7;
                        $newAsset->save();
                        $oldAsset->status = 1;
                        $oldAsset->save();
                        $Applicant->asset_id = $request->asset_id;
                    };
                }


                if ($request->has('submission_date')) {
                    $Applicant->submission_date = $request->submission_date;
                }

                if ($request->has('expiry_date')) {
                    $Applicant->expiry_date = $request->expiry_date;
                }

                $Applicant->save();

                $currentImages = Image::where('applicant_id', $Applicant->id)->first()?->path ?? [];
                $imagePaths = json_decode($currentImages, true) ?: [];


                if ($request->has('delete_images')) {
                    $deleteImages = $request->delete_images;
                    foreach ($deleteImages as $itemToDelete) {
                        if (in_array($itemToDelete, $imagePaths)) {
                            $imagePaths = array_diff($imagePaths, [$itemToDelete]);

                            try {
                                unlink(public_path('uploads/applicant/' . $itemToDelete));
                            } catch (\Exception $e) {
                            }
                        }
                    }
                }

                if ($request->hasFile('path')) {
                    $images = $request->file('path');
                    foreach ($images as $image) {
                        $filename = 'VA' . Str::random(40) . $image->getClientOriginalName();
                        $image->move(public_path('uploads/applicant'), $filename);
                        $imagePaths[] = $filename;
                    }
                }

                Image::updateOrCreate(
                    ['applicant_id' => $Applicant->id],
                    ['path' => json_encode($imagePaths)]
                );


                return response()->json([
                    "message" => "Applicant updated successfully"
                ]);
            }

            if ($request->has('type')) {
                $Applicant->type = Type::getValue($request->type);
            }




            if ($Applicant && $Applicant->status == "Belum_Disetujui" && $oldAsset->status == 'Dalam_Proses_Pengembalian') {


                if ($request->has('type') && $request->type != "2") {
                    return response()->json(['error' => 'type cannot be changed.'], 400);
                }

                if ($request->has('asset_id')) {
                    $newAsset = Asset::find($request->asset_id);


                    if ($newAsset  && $Applicant->asset_id != $request->asset_id) {
                        return response()->json(['error' => 'Asset ID cannot be changed.'], 400);
                    };

                    if (!$newAsset) {
                        return response()->json(['message' => 'item_Condition'], 400);
                    };
                }

                


                if ($request->has('submission_date')) {
                    $Applicant->submission_date = $request->submission_date;
                }

                if ($request->has('expiry_date')) {
                    $Applicant->expiry_date = $request->expiry_date;
                }

                $Applicant->save();

                $currentImages = Image::where('applicant_id', $Applicant->id)->first()?->path ?? [];
                $imagePaths = json_decode($currentImages, true) ?: [];

                if ($request->has('delete_images')) {
                    $deleteImages = $request->delete_images;
                    foreach ($deleteImages as $itemToDelete) {
                        if (in_array($itemToDelete, $imagePaths)) {
                            $imagePaths = array_diff($imagePaths, [$itemToDelete]);

                            try {
                                unlink(public_path('uploads/applicant/' . $itemToDelete));
                            } catch (\Exception $e) {

                            }
                        }
                    }
                }

                if ($request->hasFile('path')) {
                    $images = $request->file('path');
                    foreach ($images as $image) {
                        $filename = 'VA' . Str::random(40) . $image->getClientOriginalName();
                        $image->move(public_path('uploads/applicant'), $filename);
                        $imagePaths[] = $filename;
                    }
                }

                Image::updateOrCreate(
                    ['applicant_id' => $Applicant->id],
                    ['path' => json_encode($imagePaths)]
                );

                return response()->json([
                    "message" => "Applicant updated successfully"
                ]);
            }else {
                return response()->json([
                    "message" => "Cant Update"
                ]);
            }
        }else {
            return response()->json([
                "message" => "Your login not User"
            ]);
        }
    }

    public function detil($id)
    {
        if (Auth::user()->role->id == 2) {
            $applicant = Applicant::with(['asset', 'user', 'images'])
                ->where('id', $id)
                ->where('user_id', Auth::user()->id)
                ->first();

            if (!$applicant || $applicant->status !== "Belum_Disetujui") {
                return response()->json([
                    'message' => 'Applicant not found.'
                ], 404);
            }

            $response = $applicant->toArray();
            $response['type'] = (int) $applicant->getAttributes()['type'];
            $response['status'] = (int) $applicant->getAttributes()['status'];
            $response['image_assets'] = [];

            foreach ($applicant->images as $image) {
                $paths = json_decode($image->path, true);
                foreach ($paths as $path) {
                    $response['image_assets'][] = [
                        'asset_id' => $applicant->asset->id,
                        'path' => $path,
                    ];
                }
            }

            return response()->json($response, 200);
        } else {
            return response()->json([
                "message" => "Your login not User"
            ], 403);
        }
    }



    public function destroy(Request $request)
    {
        if (Auth::user()->role->id != 2) {
            return response()->json([
                "message" => "Your login is not a user"
            ]);
        }
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:applicants,id',
        ]);

        $applicants = Applicant::whereIn('id', $request->ids)
            ->where('user_id', Auth::user()->id)
            ->get();

        foreach ($applicants as $applicant) {
            if (!$applicant || $applicant->delete_user != null) {
                continue;
            }

            $asset = Asset::find($applicant->asset_id);
            $image = Image::where('applicant_id', $applicant->id)->first();

            if ($applicant->status == "Disetujui" || $applicant->status == "Ditolak") {
                $applicant->update([
                    "delete_user" => now(),
                ]);
            } elseif ($applicant->status == "Belum_Disetujui" && $applicant->type == "Peminjaman") {
                $applicant->delete();
                if ($image) {
                    $image->delete();
                }
                if ($asset) {
                    $asset->update([
                        'status' => 1,
                    ]);
                }
            } elseif ($applicant->type == "Pengembalian" && $applicant->status == "Belum_Disetujui") {
                $applicant->delete();
                if ($image) {
                    $image->delete();
                }
                if ($asset) {
                    $asset->update([
                        'status' => 3,
                    ]);
                }
            }
        }

        return response()->json([
            "message" => "Selected applicants successfully updated"
        ]);
    }

    public function getExpiredApplicants()
    {

        $expiredApplicants = Applicant::where('type', '1')
            ->where('status', '2')
            ->where('expiry_date', '>=', Carbon::now())
            ->orderBy('expiry_date', 'desc')
            ->get()
            ->groupBy('asset_id')
            ->map(function ($group) {
                return $group->first();
            })
            ->values();

        return response()->json($expiredApplicants);
    }
}
