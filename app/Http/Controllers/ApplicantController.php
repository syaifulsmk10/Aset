<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Asset;
use App\Models\image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   

 public function index(){
       $applicant = Applicant::where('user_id', Auth::user()->id)->get();

       if(!$applicant){
            return response()->json([
                "message" => "applicant null"
            ]);
       }

        $applicantdata = [];
        foreach ($applicant as $applicants) {

            $applicantdata[] = [
                "name" => $applicants->asset->asset_name,
                "kategori" => $applicants->asset->category->name,
                "tanggal pengajuan" => $applicants->submission_date,
                "tanggal masa habis" => $applicants->expiry_date,
                "tipe" => $applicants->type,
                "status" => $applicants->status

            ];
        }

         return response()->json([
        'applicantdata' => $applicantdata
    ]);
    }
    
    public function create(Request $request){

       $asset = Asset::find($request->asset_id);

        if ($asset && $asset->status === 1){

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
 
          
        }else{
              return response()->json([
                "message" => "Asset not active"
            ]);
        }
       
}
   }
