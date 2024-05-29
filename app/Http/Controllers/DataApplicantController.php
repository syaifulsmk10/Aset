<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DataApplicantController extends Controller
{
   public function index(Request $request)
{
    $query = Applicant::with(['asset.category', 'user'])->whereNull('denied_at');

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
            })->orWhere('type', 'LIKE', "%{$search}%");
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

    $applicants = $query->get();

    $dataApplicant = [];
    foreach ($applicants as $applicant) {
        $dataApplicant[] = [
            "NameAsset" => $applicant->asset->asset_name,
            "Category" => $applicant->asset->category->name,
            "SubmissionDate" => $applicant->submission_date,
            "ExpiryDate" => $applicant->expiry_date,
            "UserApplicants" => $applicant->user->name,
            "type" => $applicant->type,
        ];
    }

    return response()->json([
        "dataApplicant" => $dataApplicant
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
            $Applicant->update([
                "accepted_at" => Carbon::now(),
                'status' => 2,
            ]);
            return response()->json([
                "message" => "Accept Applicant Successfully"
            ]);
        } else {
            return response()->json([
                "message" => "Applicant cannot be accepted as they have already been accepted or denied."
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