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
   

 public function index(Request $request){
       $query = Applicant::where('user_id', Auth::user()->id)->get();

       if(!$query){
            return response()->json([
                "message" => "applicant null"
            ]);
       }

       if ($request->has('search')) {
        $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->orWhereHas('asset', function ($q) use ($search) {
                    $q->where('asset_name', 'LIKE', "%{$search}%")
                      ->orWhereHas('category', function($q) use ($search) {
                  $q->where('name', 'LIKE', "%{$search}%");
              });
            });
        });
    }

    $perpage = $request->input("per_page", 10);
    $applicant = $query->paginate($perpage);



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
        'applicantdata' => $applicantdata,
         "pagination" => [
            'total' => $applicants->total(),
            'per_page' => $applicants->perPage(),
            'current_page' => $applicants->currentPage(),
            'last_page' => $applicants->lastPage(),
            'next_page_url' => $applicants->nextPageUrl(),
            'prev_page_url' => $applicants->previousPageUrl()
        ]
    ]);
}
    
    public function create(Request $request){

       $asset = Asset::find($request->asset_id);

if ($asset) {
    if ($asset->status !== 2 || $asset->status !== 3 || $asset->status !== 4 || $asset->status !== 5 || $asset->status !== 6 || $asset->status !== 7 || $asset->status !== 8 ) {
        $applicant = Applicant::create([
            'user_id' => Auth::user()->id,
            'asset_id' => $request->asset_id,
            'submission_date' => $request->submission_date,
            'expiry_date' => $request->expiry_date,
            'type' => $request->type,
            'status' => 1,
        ]);

        $image = Image::create([
            'applicant_id' => $applicant->id,
            'path' => $request->path,
        ]);

        $asset->update([
            "status" => 7
        ]);

        return response()->json([
            'message' => 'Peminjaman berhasil'
        ]);
    } elseif ($asset->status !== 2 || $asset->status !== 1 || $asset->status !== 4 || $asset->status !== 5 || $asset->status !== 6 || $asset->status !== 7 || $asset->status !== 8 ) {
        $applicant = Applicant::create([
            'user_id' => Auth::user()->id,
            'asset_id' => $request->asset_id,
            'submission_date' => $request->submission_date,
            'expiry_date' => $request->expiry_date,
            'type' => $request->type,
            'status' => 1,
        ]);

        $image = Image::create([
            'applicant_id' => $applicant->id,
            'path' => $request->path,
        ]);


        return response()->json([
            'message' => 'Peminjaman berhasil'
        ]);
    } else {
        return response()->json([
            'message' => 'Kondisi aset tidak memenuhi syarat'
        ], 400);
    }
} else {
    return response()->json([
        'message' => 'Aset tidak ditemukan'
    ], 404);
}

    
}
   }
