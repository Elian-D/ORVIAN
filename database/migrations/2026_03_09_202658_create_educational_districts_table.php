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
        Schema::create('educational_districts', function (Blueprint $table) {
            $table->string('id')->primary(); // Ej: "01-01", "08-03"
            $table->string('regional_education_id');
            $table->string('name');
            
            $table->foreign('regional_education_id')
                ->references('id')
                ->on('regional_education')
                ->onDelete('cascade');
                
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_districts');
    }
};
