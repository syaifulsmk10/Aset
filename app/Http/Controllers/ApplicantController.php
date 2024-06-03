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

    public function index(Request $request)
    {
        $query = Applicant::where('user_id', Auth::user()->id);

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

        $perpage = $request->input("per_page", 10);
        $applicants = $query->paginate($perpage);

        $applicantdata = [];
        foreach ($applicants->items() as $applicant) {
            $applicantdata[] = [
                "name" => $applicant->asset->asset_name,
                "kategori" => $applicant->asset->category->name,
                "tanggal pengajuan" => $applicant->submission_date,
                "tanggal masa habis" => $applicant->expiry_date,
                "tipe" => $applicant->type,
                "status" => $applicant->status
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


    public function create(Request $request)
    {

        $asset = Asset::find($request->asset_id);
        // dd(is_null($asset));
        $applicant = Applicant::where("user_id", Auth::user()->id)->where("type", $request->type)->first();
        if ($asset) {
            if ($asset->status == '1' && $applicant->type == '1') {
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
                    "status" => "7"
                ]);

                return response()->json([
                    'message' => 'Peminjaman berhasil'
                ]);
            } 

            elseif ($asset->status == '3' && $applicant->type == '2') {
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
}
