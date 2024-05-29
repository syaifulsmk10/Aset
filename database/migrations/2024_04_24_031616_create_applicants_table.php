<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
             $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('asset_id')->constrained('assets');
            $table->dateTime('submission_date');
            $table->dateTime('expiry_date');
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('denied_at')->nullable();
            $table->enum('type', ['1', '2']);
            $table->enum('status', ['1', '2', '3']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
