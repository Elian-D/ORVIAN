<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_technical_titles', function (Blueprint $table) {
            // Eliminamos $table->id() y $table->timestamps()
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technical_title_id')->constrained()->cascadeOnDelete();
            
            // Agregamos la llave primaria compuesta para evitar duplicados
            $table->primary(['school_id', 'technical_title_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_technical_titles');
    }
};
