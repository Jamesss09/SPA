<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 1: academic structure (school years, periods, levels, rooms, sections, subjects).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. A.Y. 2026–2027
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('grading_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quarter_number'); // 1–4
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['school_year_id', 'quarter_number']);
        });

        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Grade 7
            $table->unsignedTinyInteger('level_number'); // 7–12
            $table->timestamps();
            $table->unique(['level_number']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->unique();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grade_level_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Grade 7 – St. Anne
            $table->foreignId('adviser_faculty_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code')->unique();
            $table->string('subject_name');
            $table->foreignId('grade_level_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('grade_levels');
        Schema::dropIfExists('grading_periods');
        Schema::dropIfExists('school_years');
    }
};
