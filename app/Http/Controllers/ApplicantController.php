<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Asset;
use App\Models\image;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicantController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        $query = Applicant::where('user_id', Auth::user()->id)->whereNull('delete_user');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->orWhereHas('asset', function ($q) use ($search) {
                    $q->where('asset_name', 'LIKE', "%{$search}%")
                        ->orWhereHas('category', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%{$search}%");
                        });
                });
            });
        }

        if($request->has("start_date") && $request->has('end_date')){
            $startdate = $request->input("start_date");
            $enddate = $request->input("end_date");

            $query->whereBetween('submission_date', [$startdate, $enddate]);
        }

         if ($request->has('status')) {
        $status = $request->input('status');
        $query->where('status', $status);
        }

        $perpage = $request->input("per_page", 10);
        $applicants = $query->paginate($perpage);
        $applicantdata = [];
        foreach($applicants as $applicant){
            $applicantdata[] = [
                "name" => $applicant->asset->asset_name,
                "kategori" => $applicant->asset->category->name,
                "tanggal pengajuan" => $applicant->submission_date,
                "tanggal masa habis" => $applicant->expiry_date,
                "tipe" => $applicant->type,
                "status" => $applicant->status
            ];
        }

        // $applicantdata = collect($applicants)->map(function ($applicant) {
        //     return [
        //         "name" => $applicant->asset->asset_name,
        //         "kategori" => $applicant->asset->category->name,
        //         "tanggal pengajuan" => $applicant->submission_date,
        //         "tanggal masa habis" => $applicant->expiry_date,
        //         "tipe" => $applicant->type,
        //         "status" => $applicant->status
        //     ];
        // })->all();

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


    public function create(Request $request)
    {


       

      
         $asset = Asset::find($request->asset_id);
        if ($asset) {
           
            if ($asset->status == 'Aktif' && $request->type == 1 && $asset->item_condition == "Baik" ) {
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
                foreach ($images as $image) {
                     $imageName = $image->getClientOriginalName();
                     $image->move(public_path('path'), $imageName);
                     image::create([
                         'applicant_id' => $applicant->id,
                         'path' => $imageName,
                     ]);
                }
        } else {
         return response()->json(['error' => 'No file found'], 400);
            }  
             
            $asset->update([
                    "status" => "7"
                ]);

                return response()->json([
                    'message' => 'Peminjaman berhasil'
                ]);
            } 

            elseif ($asset->status == 'Dipinjamkan' &&$request->type == 2) {
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


            foreach ($images as $image) {
                $imageName = $image->getClientOriginalName();
                $image->move(public_path('path'), $imageName);
                image::create([
                    'applicant_id' => $applicant->id,
                    'path' => $imageName,
            ]);
                }
            } else {
                return response()->json(['error' => 'No file found'], 400);
            }   

                 $asset->update([
                    "status" => "7"
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

     public function delete($id)
    {
        $Applicant = Applicant::find($id);
        $asset = Asset::find($Applicant->asset_id);
        $image = Image::where('applicant_id', $Applicant->id)->first();
        if ($Applicant->status == "Disetujui" || $Applicant->status == "Ditolak") {
             $Applicant->update([
            "delete_user" => now(),
        ]);
            return response()->json([
            "message" =>  "success delete",
        ]);
        }elseif($Applicant && $Applicant->status == "Belum_Disetujui" && $Applicant->type == "Peminjaman"){
              $Applicant->delete();
            $image->delete();
            $asset->update([
                'status' => 1,
            ]);
            return response()->json([
            "message" =>  "success delete",
                ]);
        }elseif($Applicant && $Applicant->type == "Pengembalian" && $Applicant->status == "Belum_Disetujui"  ){
            $Applicant->delete();
             $image->delete();
            $asset->update([
                'status' => 3,
            ]);


            return response()->json([
            "message" =>  "success delete",
                ]);
        }else{
             return response()->json([
                "message" => "cant delete",
            ]);
        }
      
    }


     public function detail($id)
    {
        $Applicant = Applicant::with(['asset', 'user', 'images'])->where('user_id', Auth::user()->id)->find($id);

        // $images = collect($Applicant->image)->map(function ($image) {
        //     return $image->path;
        // })->all();

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


    public function update(Request $request, $id)
    {

        
        $Applicant = Applicant::where('id', $id)->where('user_id', Auth::user()->id)->first();
        $asset = Asset::find($request->asset_id);

       

        if($Applicant && $Applicant->status == "Belum_Disetujui"){
        

        if ($request->has('submission_date')) {
            $Applicant->submission_date = $request->submission_date;
        }

        if ($request->has('expiry_date')) {
            $Applicant->expiry_date = $request->expiry_date;
        }


        

        $Applicant->save();
        
        if ($request->has('path')) {
            $image = Image::where('Applicant_id', $Applicant->id)->first();
            $ImagePath = $request->file('path')->move(public_path(), $request->file('path')->getClientOriginalName());
            $ImageName = $request->file('path')->getClientOriginalName();

              if(!$ImagePath){
                return response()->json([
                    "message" => "failed to upload image"
                ]);
            }

            if($image){
                $image->update([
                    "path" => $ImageName,
                ]);
            }
            ;

            
        }

         return response()->json([
            "message" => "Applicant updated successfully"
        ]);
        }else{
            return response()->json([
            "message" => "Cant Update"
        ]);
        }
        
    }   

    public function detil($id){
        $Applicant =    Applicant::find($id);

        if ($Applicant && $Applicant->status == "Belum_Disetujui") {
             $Applicantdata = [];
             $Applicantdata[] = [
                 "name" => $Applicant->asset->asset_name,
                "kategori" => $Applicant->asset->category->name,
                "tanggal pengajuan" => $Applicant->submission_date,
                "tanggal masa habis" => $Applicant->expiry_date,
                "tipe" => $Applicant->type,
                "status" => $Applicant->status
            ];

         return response()->json([
        'message' => $Applicantdata
    ]);
        }
    }
}
