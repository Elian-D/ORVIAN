<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_subject_sections', function (Blueprint $table) {
            $table->foreignId('school_id')
                ->nullable()
                ->after('id')
                ->constrained('schools')
                ->cascadeOnDelete();
        });

        // Rellenar school_id desde la sección relacionada
        DB::table('teacher_subject_sections')->get()->each(function ($row) {
            $section = DB::table('school_sections')->find($row->school_section_id);
            if ($section) {
                DB::table('teacher_subject_sections')
                    ->where('id', $row->id)
                    ->update(['school_id' => $section->school_id]);
            }
        });

        Schema::table('teacher_subject_sections', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_subject_sections', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
        });
    }
};
