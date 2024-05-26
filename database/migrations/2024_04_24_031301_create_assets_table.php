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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code');
            $table->string('asset_name');
            $table->foreignId('category_id')->constrained('categories');
            $table->enum('item_condition', range(1, 7));
            $table->bigInteger('price');
            $table->dateTime('received_date');
            $table->dateTime('expiration_date');
            $table->enum('status', range(1, 8));
            $table->string('image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
