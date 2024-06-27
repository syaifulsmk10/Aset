<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Asset;
use App\Models\Image;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DataApplicantController extends Controller
{
    public function index(Request $request)
    {
        $query = Applicant::with(['asset.category', 'user'])->whereNull('denied_at')->whereNull('delete_admin');

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
            $query->where('type', $type);
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query->whereBetween('submission_date', [$startDate, $endDate]);
        }


        $perpage = $request->input('per_page', 10);
        $applicants = $query->paginate($perpage);

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
        return response()->json($applicants);
    }



    public function detail($id)
    {
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
    }


    public function accept($id)
    {
        $Applicant = Applicant::find($id);
        if (!$Applicant) {
            return response()->json([
                "message" => "Applicant Not Found"
            ]);
        }

        if ($Applicant && $Applicant->accepted_at === null && $Applicant->denied_at === null) {
            $Applicant->update([
                "accepted_at" => Carbon::now(),
                'status' => 2,
            ]);


            $Asset = Asset::find($Applicant->asset_id);

            if ($Asset) {
                if ($Applicant->type == "Peminjaman") {
                    $Asset->update([
                        'status' => 3,
                    ]);
                } elseif ($Applicant->type == "Pengembalian") {
                    $Asset->update([
                        'status' => 1,
                    ]);
                }
            }

            return response()->json([
                "message" => "Accept Applicant Successful "
            ]);
        } else {
            return response()->json([
                "message" => "Applicant cannot be accepted because they have been accepted or rejected previously."
            ], 400);
        }
    }




    public function denied($id)
    {
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
    }


    public function delete($id)
    {
        $Applicant = Applicant::find($id);
        if (!$Applicant) {
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
        };
    }
}
