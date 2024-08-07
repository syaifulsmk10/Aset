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
            $table->foreignId('user_id')->constrained('users')->onDelete("cascade");
            $table->foreignId('asset_id')->constrained('assets')->onDelete("cascade");
            $table->dateTime('submission_date')->nullable();;
            $table->dateTime('expiry_date')->nullable();;
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('denied_at')->nullable();
            $table->enum('type', ['1', '2']);
            $table->enum('status', ['1', '2', '3']);
            $table->dateTime('delete_admin')->nullable();
            $table->dateTime('delete_user')->nullable();
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
