<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Asset;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DataApplicantController extends Controller
{
   public function index(Request $request)
{
    $query = Applicant::with(['asset.category', 'user'])->whereNull('denied_at');

    if(!$query){
            return response()->json([
                "message" => "not request applicant"
            ]);
    }

    if ($request->has('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->whereHas('asset', function($q) use ($search) {
                $q->where('asset_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('category', function($q) use ($search) {
                      $q->where('name', 'LIKE', "%{$search}%");
                  });
            })->orWhereHas('user', function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
            });
        });
    }

    if($request->has('type')){
         $type = $request->input('type');
         $query->where('type', $type);
    }

    if($request->has('status')){
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

    $dataApplicant = [];
    foreach ($applicants as $applicant) {
        $dataApplicant[] = [
            "NameAsset" => $applicant->asset->asset_name,
            "Category" => $applicant->asset->category->name,
            "SubmissionDate" => $applicant->submission_date,
            "ExpiryDate" => $applicant->expiry_date,
            "UserApplicants" => $applicant->user->name,
            "type" => $applicant->type
        ];
    }

    return response()->json([
        "dataApplicant" => $dataApplicant,
         "pagination" => [
            'total' => $applicant->total(),
            'per_page' => $applicant->perPage(),
            'current_page' => $applicant->currentPage(),
            'last_page' => $applicant->lastPage(),
            'next_page_url' => $applicant->nextPageUrl(),
            'prev_page_url' => $applicant->previousPageUrl()
        ]
    ]);
}


    public function detail($id)
    {
        $Applicant = Applicant::with(['asset', 'user', 'images'])->find($id);

        $images = [];
        foreach ($Applicant->images as $image) {
            $images[] = $image->path;
        }

        $dataApplicant = [
            "NameAsset" => $Applicant->asset->asset_name,
            "Category" => $Applicant->asset->category->name,
            "SubmissionDate" => $Applicant->submission_date,
            "ExpiryDate" => $Applicant->expiry_date,
            "UserApplicants" => $Applicant->user->name,
            "type" => $Applicant->type,
            "Images" => $images // Kumpulkan URL gambar dalam array
        ];

        return response()->json([
            "dataApplicant" => $dataApplicant
        ]);
    }


    public function accept($id)
{
    $Applicant = Applicant::find($id);

    if ($Applicant && $Applicant->accepted_at === null && $Applicant->denied_at === null) {
        // Perbarui tanggal diterima dan status peminjam
        $Applicant->update([
            "accepted_at" => Carbon::now(),
            'status' => 2,         
        ]);

        // Temukan aset terkait
        $Asset = Asset::find($Applicant->asset_id);

        if ($Asset) {
            // Perbarui status aset berdasarkan tipe peminjam
            if ($Applicant->type == 1) {
                $Asset->update([
                    'status' => 3,
                ]);
            } elseif ($Applicant->type == 2) {
                $Asset->update([
                    'status' => 1,
                ]);
            }
        }

        return response()->json([
            "message" => "Terima Peminjam Berhasil"
        ]);
    } else {
        return response()->json([
            "message" => "Peminjam tidak dapat diterima karena mereka sudah diterima atau ditolak sebelumnya."
        ], 400);
    }
}




    public function denied($id)
    {
        $Applicant = Applicant::find($id);

        if ($Applicant && $Applicant->accepted_at === null && $Applicant->denied_at === null) {
            $Applicant->update([
                "denied_at" => Carbon::now(),
                "status" => 3,
            ]);
            return response()->json([
                "message" => "Denied Applicant Successfully"
            ]);
        } else {
            return response()->json([
                "message" => "Applicant cannot be accepted as they have already been accepted or denied."
            ], 400);
        }
    }
}