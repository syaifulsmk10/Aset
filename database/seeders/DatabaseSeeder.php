<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Applicant;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Department;
use App\Models\Employee;
use App\Models\image;
use App\Models\ImageAsset;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
   public function run(): void
{
        Role::create([
            "name" => "admin",
        ]);
        Role::create([
            "name" => "user",
        ]);

    // Admin
    User::create([
        "name" => "admin",
        "username" => "admin",
        "email" => "admin@admin.com",
        "password" => bcrypt("admin_password"),
        "role_id" => 1, // Menggunakan role_id
    ]);

    // Siswa
    User::create([
        "name" => "user",
        "username" => "user",
         "email" => "user@user.com",
        "password" => bcrypt("user_password"),
        "role_id" => 2, // Menggunakan role_id
    ]);

     Department::create([
        "name" => "back end"
    ]);

    Position::create([
        "name" => "karyawan",
    ]);


    Employee::create([
        "user_id" => 2,
        "nip" => 1234556,
        "department_id" => 1,
        "position_id" => 1,
    ]);

     Employee::create([
        "user_id" => 1,
        "nip" => 1235511,
        "department_id" => 1,
        "position_id" => 1,
    ]);

    Category::create([
        "name" => "Peralatan IT"
    ]);

    category::create([
        "name" => "Inventaris"
    ]);

    category::create([
        "name" => "Peralatan Kantor"
    ]);

    category::create([
        "name" => "Perabotan"
    ]);

    category::create([
        "name" => "Perangkat Elektronik Konsumen"
    ]);

    category::create([
        "name" => "Peralatan Medis"
    ]);

    category::create([
        "name" => "Koleksi Seni dan Barang Koleksi"
    ]);

    category::create([
        "name" => "Peralatan Olahraga dan Rekreasi"
    ]);

    category::create([
        "name" => "Keamanan dan Perlindungan"
    ]);

    category::create([
        "name" => "Peralatan Komunikasi"
    ]);
   

    Asset::create([
        "asset_code"  => 111,
        "asset_name" => "laptop",
        "category_id" => 1,
        "item_condition" => 1,
        "price" => 2000000,
        "received_date" => Carbon::now(),
        "expiration_date" => Carbon::now()->addDays(10),
        "status" => 1,

    ]);

    Asset::create([
        "asset_code"  => 222,
        "asset_name" => "sepeda",
        "category_id" => 2,
        "item_condition" => 1,
        "price" => 1000000,
        "received_date" => Carbon::now(),
        "expiration_date" => Carbon::now()->addDays(8),
        "status" => 1,
    ]);

    


    Applicant::create([
        "user_id" => 2,
        "asset_id" => 1,
        "submission_date" => Carbon::now(),
        "expiry_date" => Carbon::now()->addDays(8),
        "accepted_at" => Carbon::now(),
        "status" => 2,
        "type" => "1",
    ]);

    Applicant::create([
        "user_id" => 2,
        "asset_id" => 2,
        "submission_date" => Carbon::now(),
        "expiry_date" => Carbon::now()->addDays(8),
        "accepted_at" => Carbon::now(),
        "status" => 2,
        "type" => "1",
    ]);


    image::create([
        "applicant_id" => 1,
        "path" => "image.jpg",
        
    ]);





    
}

}
