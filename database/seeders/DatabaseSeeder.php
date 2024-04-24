<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
   public function run(): void
{
    // Admin
    User::create([
        "name" => "admin",
        "password" => bcrypt("admin_password"),
        "roles_id" => 1, // Menggunakan roles_id
    ]);

    // Kantin
    User::create([
        "name" => "kantin",
        "password" => bcrypt("kantin_password"),
        "roles_id" => 2, // Menggunakan roles_id
    ]);

    // Bank
    User::create([
        "name" => "bank",
        "password" => bcrypt("bank_password"),
        "roles_id" => 3, // Menggunakan roles_id
    ]);

    // Siswa
    User::create([
        "name" => "siswa",
        "password" => bcrypt("siswa_password"),
        "roles_id" => 4, // Menggunakan roles_id
    ]);
}

}
