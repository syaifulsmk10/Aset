<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Asset;
use App\Models\image;
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
                $query->where('status', $status);
            }

            //pagination
            $perpage = $request->input("per_page", 10);
            $applicants = $query->paginate($perpage);

            return response()->json($applicants);
        } else {
            return response()->json([
                "message" => "Your login not User"
            ]);
        }
    }



    public function create(Request $request)
    {
        if (Auth::user()->role->id == 2) {
            $validator = Validator::make($request->all(), [
                'asset_id' => 'required|exists:assets,id',
                'submission_date' => 'required|date',
                'expiry_date' => 'required|date|after:submission_date',
                'type' => 'required|in:1,2',
                'path' => 'required|array|min:1',
                'path.*' => 'required||image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }


            $asset = Asset::find($request->asset_id);
            if ($asset) {
                if ($asset->status == 'Aktif' && $request->type == 1 && $asset->item_condition == "Baik") {
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

                        image::create([
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

                        image::create([
                            'applicant_id' => $applicant->id,
                            'path' => json_encode($imagePaths),
                        ]);
                        $asset->status = 7;
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
            if (!$Applicant) {
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
                "NameAsset" => $Applicant->asset->asset_name,
                "Category" => $Applicant->asset->category->name,
                "SubmissionDate" => $Applicant->submission_date,
                "ExpiryDate" => $Applicant->expiry_date,
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
            $validator = Validator::make($request->all(), [
                'asset_id' => 'sometimes|required|exists:assets,id',
                'submission_date' => 'sometimes|required|date',
                'expiry_date' => 'sometimes|required|date|after:submission_date',
                'type' => 'sometimes|required|in:1,2',
                'path.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $Applicant = Applicant::where('id', $id)->where('user_id', Auth::user()->id)->first();
            if (!$Applicant) {
                return response()->json([
                    'message' => 'Applicant not found.'
                ], 404);
            }
            $oldAsset = Asset::find($Applicant->asset_id);




            if ($Applicant && $Applicant->status == "Belum_Disetujui" && $oldAsset->status == 'Dalam_Proses_Peminjaman') {

                if ($request->has('type') && $request->type != 1) {
                    return response()->json(['error' => 'Type ID cannot be changed.'], 400);
                }

                if ($request->has('asset_id')) {
                    $newAsset = Asset::where('id', $request->asset_id)->where('item_condition', '1')->where('status', 1)->first();
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

                if ($request->hasFile('path')) {
                    $images = $request->file('path');
                    $imagePaths = [];


                    $oldImages = image::where('applicant_id', $Applicant->id)->first();
                    if ($oldImages) {
                        $oldImagePaths = json_decode($oldImages->path, true);
                        foreach ($oldImagePaths as $oldImagePath) {
                            $oldImageFullPath = public_path('uploads/applicant/' . $oldImagePath);
                            if (file_exists($oldImageFullPath)) {
                                unlink($oldImageFullPath);
                            }
                        }
                        $oldImages->delete();
                    }

                    foreach ($images as $image) {
                        $imageName = 'VA' . Str::random(40) . $image->getClientOriginalName();
                        $image->move(public_path('uploads/applicant'), $imageName);
                        $imagePaths[] = $imageName;
                    }

                    image::create([
                        'applicant_id' => $Applicant->id,
                        'path' => json_encode($imagePaths),
                    ]);

                    return response()->json([
                        "message" => "Success Update Asset"
                    ], 200);
                }


                return response()->json([
                    "message" => "Applicant updated successfully"
                ]);
            }

            if ($Applicant && $Applicant->status == "Belum_Disetujui" && $oldAsset->status == 'Dipinjamkan') {


                if ($request->has('type') && $request->type != 2) {
                    return response()->json(['error' => 'type cannot be changed.'], 400);
                }

                if ($request->has('asset_id')) {
                    $newAsset = Asset::find($request->asset_id);

                    if ($newAsset  && $Applicant->asset_id != $request->asset_id) {
                        return response()->json(['error' => 'Asset ID cannot be changed.'], 400);
                    };
                }

                if (!$newAsset) {
                    return response()->json(['message' => 'item_Condition'], 400);
                };


                if ($request->has('submission_date')) {
                    $Applicant->submission_date = $request->submission_date;
                }

                if ($request->has('expiry_date')) {
                    $Applicant->expiry_date = $request->expiry_date;
                }

                $Applicant->save();

                if ($request->hasFile('path')) {
                    $images = $request->file('path');
                    $imagePaths = [];


                    $oldImages = image::where('applicant_id', $Applicant->id)->first();
                    if ($oldImages) {
                        $oldImagePaths = json_decode($oldImages->path, true);
                        foreach ($oldImagePaths as $oldImagePath) {
                            $oldImageFullPath = public_path('uploads/applicant/' . $oldImagePath);
                            if (file_exists($oldImageFullPath)) {
                                unlink($oldImageFullPath);
                            }
                        }
                        $oldImages->delete();
                    }

                    foreach ($images as $image) {
                        $imageName = 'VA' . Str::random(40) . $image->getClientOriginalName();
                        $image->move(public_path('uploads/applicant'), $imageName);
                        $imagePaths[] = $imageName;
                    }

                    image::create([
                        'applicant_id' => $Applicant->id,
                        'path' => json_encode($imagePaths),
                    ]);

                    return response()->json([
                        "message" => "Success Update Asset"
                    ], 200);
                }
            } else {
                return response()->json([
                    "message" => "Cant Update"
                ]);
            }
        } else {
            return response()->json([
                "message" => "Your login not User"
            ]);
        }
    }

    public function detil($id)
    {
        if (Auth::user()->role->id == 2) {
            $Applicant = Applicant::with(['asset', 'user'])->where('id', $id)->where('user_id', Auth::user()->id)->first();

            if (!$Applicant || $Applicant->status !== "Belum_Disetujui") {
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
                "NameAsset" => $Applicant->asset->asset_name,
                "Category" => $Applicant->asset->category->name,
                "SubmissionDate" => $Applicant->submission_date,
                "ExpiryDate" => $Applicant->expiry_date,
                "UserApplicants" => $Applicant->user->name,
                "type" => $Applicant->type,
                "Images" => $Applicant->images->map(function ($image) {
                    $data = json_decode($image->path, true);

                    return array_values(
                        array_map(fn ($path) =>  $path, $data)
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
}
