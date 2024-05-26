<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DataApplicantController extends Controller
{
    public function index()
    {
        $Applicant = Applicant::where('denied_at', null)->get();

        $dataApplicant = [];
        foreach ($Applicant as $Applicants) {
            $dataApplicant[] = [

                "NameAsset" => $Applicants->asset->asset_name,
                "Category" => $Applicants->asset->category->name,
                "SubmissionDate" => $Applicants->submission_date,
                "ExpiryDate" => $Applicants->expiry_date,
                "UserApplicants" => $Applicants->user->name,
                "type" => $Applicants->type,
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
                "accepted_at" => Carbon::now()
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
                "denied_at" => Carbon::now()
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