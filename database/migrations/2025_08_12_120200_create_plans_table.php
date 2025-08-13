<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('price', 8, 2)->default(0.00);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false);
            $table->foreignId('city_id')->constrained('cities');
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('plans');
    }
};
