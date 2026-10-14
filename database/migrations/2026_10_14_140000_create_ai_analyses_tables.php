<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained();
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('initiated_by')->constrained('users');
            $table->string('status')->default('queued');
            $table->string('progress')->nullable();
            $table->string('analysis_type')->default('visit_brief');
            $table->json('selected_report_ids');
            $table->json('context_snapshot')->nullable();
            $table->json('structured_output')->nullable();
            $table->text('original_summary')->nullable();
            $table->text('clinician_summary')->nullable();
            $table->text('approved_summary')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->json('reviewed_findings')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->string('prompt_version')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['appointment_id', 'status']);
        });

        Schema::create('ai_analysis_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_analysis_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['ai_analysis_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_audits');
        Schema::dropIfExists('ai_analyses');
    }
};
