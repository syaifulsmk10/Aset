<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Asset;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getDashboardData(){
        $totalAssets = Asset::count();
        $totalActiveAssets = Asset::where('status', 1)->count();
        $totalDamagedAssets = Asset::where('item_condition', 3)->count();
        $totalLoanedAssets = Asset::where('status', 3)->count();
        $nearestReturn = Applicant::where('expiry_date', '>=', Carbon::now())->where("type",1 )
            ->orderBy('expiry_date', 'asc')
            ->first();
            

        $datanearestReturn = [];
        if($nearestReturn){
            $datanearestReturn[] = [
                'name' => $nearestReturn->user->name,
                'assetname' => $nearestReturn->asset->asset_name,
                'expiry_date' => $nearestReturn->expiry_date
            ];
        } 

        

         $categories = Category::with('assets')->get();

        $data = [];
        foreach ($categories as $category) {
            $totalPrice = 0;
            foreach ($category->assets as $asset) {
                $totalPrice += $asset->price;
            }
            $data[] = [
                'category' => $category->name,
                'total_price' => $totalPrice
            ];
        }
         
         


             return response()->json([
            'total_assets' => $totalAssets,
            'total_active_assets' => $totalActiveAssets,
            'total_damaged_assets' => $totalDamagedAssets,
            'total_loaned_assets' => $totalLoanedAssets,
            'datanearestReturn' => $datanearestReturn,
            'data' => $data
            
        ]);
    }


}
