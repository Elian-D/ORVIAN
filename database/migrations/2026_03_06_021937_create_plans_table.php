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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('limit_students')->default(100);
            $table->integer('limit_users')->default(10);
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('const_name')->nullable();
            
            // Campos de diseño
            $table->string('bg_color')->default('#E5E7EB'); // Default gray-200
            $table->string('text_color')->default('#374151'); // Default gray-700
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
