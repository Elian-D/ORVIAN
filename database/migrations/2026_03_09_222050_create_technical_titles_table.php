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
        Schema::create('technical_titles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Ej: IFC006_3
            $table->foreignId('technical_family_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->tinyInteger('level')->nullable(); // 2 o 3
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_titles');
    }
};
