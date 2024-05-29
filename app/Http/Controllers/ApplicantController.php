<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicantController extends Controller
{
    public function index(){
       $applicant = Applicant::where('user_id', Auth::user()->id)->get();

        $applicantdata = [];
        foreach ($applicant as $applicants) {

              if(!$applicants->accepted_at && !$applicants->denied_at){
                $status = "belum disetujui";
            }if($applicants->accepted_at && !$applicants->denied_at){
                $status = "disetujui";
            }
            if(!$applicants->accepted && $applicants->denied_at ){
                $status = "ditolak";
            }

            $applicantdata[] = [
                "name" => $applicants->asset->asset_name,
                "kategori" => $applicants->asset->category->name,
                "tanggal pengajuan" => $applicants->submission_date,
                "tanggal masa habis" => $applicants->expiry_date,
                "tipe" => $applicants->type,
                "status" => 1

            ];
        }

         return response()->json([
        'applicantdata' => $applicantdata
    ]);
    }
    
    public function create(Request $request){
        $applicant = Applicant::create([
            'user_id' => Auth::user()->id,
            'asset_id' => $request->asset_id,
            'submission_date' => $request->submission_date,
            'expiry_date' => $request->expiry_date,
            'type' => $request->type,
            'status' => 1,
        ]);

        $image = image::create([
            'applicant_id' => $applicant->id,
            'path' => $request->path
        ]);
 
        return response()->json([
            'message' => 'applicant successfully'
        ]);
    }
}
