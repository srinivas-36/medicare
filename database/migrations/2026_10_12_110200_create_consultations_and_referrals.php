<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained();
            $table->foreignId('patient_id')->constrained('users');
            $table->text('clinical_notes')->nullable();
            $table->string('diagnosis')->nullable();
            $table->text('plan')->nullable();
            $table->string('ai_status')->default('skipped');
            $table->text('ai_summary')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->timestamp('ai_generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained();
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('from_doctor_id')->constrained('doctors');
            $table->foreignId('to_doctor_id')->constrained('doctors');
            $table->text('reason');
            $table->json('summary_snapshot');
            $table->string('status')->default('sent');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['to_doctor_id', 'status']);
            $table->index('from_doctor_id');
        });

        Schema::create('referral_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_report_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['referral_id', 'appointment_report_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_reports');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('consultations');
    }
};
