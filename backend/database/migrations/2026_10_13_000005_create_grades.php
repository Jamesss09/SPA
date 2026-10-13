<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 1: quarterly grades (draft, submitted, returned, finalized).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grading_period_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamps();
            $table->unique(
                ['teaching_assignment_id', 'grading_period_id'],
                'gs_unique_assignment_period'
            );
        });

        Schema::create('grade_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->decimal('grade_value', 5, 2)->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['grade_submission_id', 'enrollment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_entries');
        Schema::dropIfExists('grade_submissions');
    }
};
