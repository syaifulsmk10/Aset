<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Asset;
use App\Models\Image;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataApplicantController extends Controller
{
    public function index(Request $request)
    {

        if (Auth::user()->role->id == 1) {
            $query = Applicant::with(['asset.category', 'user'])->whereNull('delete_admin');

            if (!$query->exists()) {
                return response()->json([
                    "message" => "no applicant found"
                ]);
            }

            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('asset', function ($q) use ($search) {
                    $q->where('asset_name', 'LIKE', "%{$search}%")
                        ->orWhereHas('category', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%{$search}%");
                        });
                })->orWhereHas('user', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%");
                });
            });

            if ($request->has('type')) {
                $type = $request->input('type');
                if (is_array($type)) {
                    $query->whereIn('type', $type);
                } else {
                    $query->where('type', $type);
                }
            }

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

                $query->whereBetween('submission_date', [$startDate, $endDate]);
            }


            $perpage = $request->input('per_page', 10);
            $applicants = $query->paginate($perpage);
            $totalpage = $applicants->lastPage();

            foreach ($applicants as $applicant) {
                $assetName = $applicant->asset ? $applicant->asset->asset_name : null;
                $categoryName = $applicant->asset && $applicant->asset->category ? $applicant->asset->category->name : null;
                $userName = $applicant->user ? $applicant->user->name : null;

                $dataApplicant[] = [
                    "id" => $applicant->id,
                    "NameAsset" => $assetName,
                    "Category" => $categoryName,
                    "SubmissionDate" => $applicant->submission_date,
                    "ExpiryDate" => $applicant->expiry_date,
                    "UserApplicants" => $userName,
                    "type" => $applicant->type
                ];
            }
            return response()->json([
                "data" => $applicants,
                "total_page" => $totalpage
            ]);

        } else {
            return response()->json([
                "message" => "Your login not admin"
            ]);
        }

    }



    public function detail($id)
    {
        if (Auth::user()->role->id == 1) {
            $Applicant = Applicant::with(['asset', 'user', 'images'])->find($id);
            if (!$Applicant) {
                return response()->json([
                    "message" => "Applicant Not Found"
                ]);
            }


            $images = [];
            foreach ($Applicant->images as $image) {
                $images[] = $image->path;
            }

            $dataApplicant = [];
            $dataApplicant = [
                "id" => $Applicant->id,
                "NameAsset" => $Applicant->asset->asset_name,
                "Category" => $Applicant->asset->category->name,
                "SubmissionDate" => $Applicant->submission_date,
                "ExpiryDate" => $Applicant->expiry_date,
                "UserApplicants" => $Applicant->user->name,
                "type" => $Applicant->type,
                'status' => $Applicant->status,
                "Images" => $Applicant->images->map(function ($image) {
                    $data = json_decode($image->path, true);

                    return array_values(
                        array_map(fn($path) => env('APP_URL') . 'uploads/applicant/' . $path, $data)
                    );
                })->flatten(1)->all() // Kumpulkan URL gambar dalam array
            ];

            return response()->json($dataApplicant);
        } else {
            return response()->json([
                "message" => "Your login not admin"
            ]);
        }
    }



    public function accept($id)
    {
        if (Auth::user()->role->id == 1) {
            $Applicant = Applicant::find($id);
            if (!$Applicant) {
                return response()->json([
                    "message" => "Applicant Not Found"
                ]);
            }

            if ($Applicant && $Applicant->accepted_at === null && $Applicant->denied_at === null) {
                $Asset = Asset::where('id', $Applicant->asset_id)->first(); // Asumsi ada kolom asset_id di Applicant

                if ($Asset) {
                    // Pengecekan status Asset
                    if (($Applicant->type == "Peminjaman" && ($Asset->status == "Aktif" || $Asset->status == "Dalam_Proses_Peminjaman")) ||
                        ($Applicant->type == "Pengembalian")
                    ) {

                        $Applicant->update([
                            "accepted_at" => Carbon::now(),
                            'status' => 2,
                        ]);

                        // Update status Asset
                        if ($Applicant->type == "Peminjaman") {
                            $Asset->update([
                                'status' => 3,
                            ]);
                        } elseif ($Applicant->type == "Pengembalian") {
                            $Asset->update([
                                'status' => 1,
                            ]);
                        }

                        return response()->json([
                            "message" => "Accept Applicant Successful"
                        ]);
                    } else {
                        return response()->json([
                            "message" => "Asset status not allowed for acceptance."
                        ], 400);
                    }
                } else {
                    return response()->json([
                        "message" => "Asset not found."
                    ], 404);
                }
            } else {
                return response()->json([
                    "message" => "Applicant cannot be accepted because they have been accepted or rejected previously."
                ], 400);
            }
        } else {
            return response()->json([
                "message" => "Your login is not admin."
            ], 403);
        }
    }




    public function denied($id)
    {

        if (Auth::user()->role->id == 1) {
            $Applicant = Applicant::find($id);
            if (!$Applicant) {
                return response()->json([
                    "message" => "Applicant Not Found"
                ]);
            }

            if ($Applicant && $Applicant->accepted_at === null && $Applicant->denied_at === null) {
                $Applicant->update([
                    "denied_at" => Carbon::now(),
                    "status" => 3,
                ]);

                $Asset = Asset::find($Applicant->asset_id);

                if ($Asset) {
                    if ($Applicant->type == "Peminjaman") {
                        $Asset->update([
                            'status' => 1,
                        ]);
                    } elseif ($Applicant->type == "Pengembalian") {
                        $Asset->update([
                            'status' => 3,
                        ]);
                    }
                }

                return response()->json([
                    "message" => "Denied Applicant Successfully"
                ]);
            } else {
                return response()->json([
                    "message" => "Applicant cannot be accepted as they have already been accepted or denied."
                ], 400);
            }
        } else {
            return response()->json([
                "message" => "Your login not admin"
            ]);
        }
    }


    public function delete($id)
    {
        if (Auth::user()->role->id == 1) {
            $Applicant = Applicant::find($id);
            if (!$Applicant|| $Applicant->delete_admin != null) {
                return response()->json([
                    "message" => "Applicant Not Found"
                ]);
            }
            if ($Applicant->status == "Disetujui" || $Applicant->status == "Ditolak") {
                $Applicant->update([
                    "delete_admin" => now(),
                ]);

                return response()->json([
                    "message" => "Success Delete"
                ]);
            } else {
                return response()->json([
                    "message" => "Cant Delete, Please Acc/deneid"
                ]);
            }
            ;
        } else {
            return response()->json([
                "message" => "Your login not admin"
            ]);
        }   
    }

    public function destroy(Request $request)
    {
        if (Auth::user()->role->id != 1) {
            return response()->json([
                "message" => "Your login is not admin"
            ]);
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:applicants,id',
        ]);

        $applicants = Applicant::whereIn('id', $request->ids)->get();

        foreach ($applicants as $applicant) {
            if ($applicant->delete_admin != null) {
                return response()->json([
                    "message" => "Applicant Not Found or Already Deleted"
                ]);
            }

            if ($applicant->status == "Disetujui" || $applicant->status == "Ditolak") {
                $applicant->update([
                    "delete_admin" => now(),
                ]);
            } else {
                return response()->json([
                    "message" => "Can't Update, Please Approve/Reject First"
                ]);
            }
        }

        return response()->json([
            "message" => "Selected applicants successfully updated"
        ]);
    }
}
