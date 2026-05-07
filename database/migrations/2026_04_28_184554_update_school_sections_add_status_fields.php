<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_sections', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('technical_title_id');
            $table->softDeletes()->after('updated_at'); // deleted_at
        });
    }

    public function down(): void
    {
        Schema::table('school_sections', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->dropSoftDeletes();
        });
    }
    };
