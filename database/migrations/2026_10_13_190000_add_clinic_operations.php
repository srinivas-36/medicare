<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('booking_horizon_months')->default(6);
            $table->string('timezone')->default('UTC');
            $table->unsignedSmallInteger('minimum_notice_minutes')->default(0);
            $table->unsignedSmallInteger('cancellation_notice_hours')->default(0);
            $table->string('payment_policy')->default('none');
            $table->timestamps();
        });

        DB::table('clinic_settings')->insert([
            'booking_horizon_months' => 6,
            'timezone' => (string) config('app.timezone', 'UTC'),
            'minimum_notice_minutes' => 0,
            'cancellation_notice_hours' => 0,
            'payment_policy' => 'none',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('doctor_availability_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('kind');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('title');
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'starts_on', 'ends_on']);
        });

        Schema::create('appointment_reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('from_date');
            $table->time('from_time');
            $table->time('from_ending')->nullable();
            $table->date('to_date');
            $table->time('to_time');
            $table->time('to_ending')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('source')->nullable();
            $table->foreignId('referral_id')->nullable()->constrained('referrals')->nullOnDelete();
            $table->foreignId('rescheduled_from_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('active_slot')->nullable()->unique();
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE appointments MODIFY status ENUM('pending','confirmed','checked_in','completed','cancelled','no_show') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('referrals', function (Blueprint $table) {
            $table->string('urgency')->default('routine');
            $table->text('clinical_question')->nullable();
            $table->text('decline_reason')->nullable();
            $table->foreignId('proposed_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->date('follow_up_on')->nullable();
            $table->text('follow_up_instructions')->nullable();
        });

        Schema::table('appointment_messages', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        $seen = [];

        DB::table('appointments')
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $row) use (&$seen): void {
                $time = substr((string) $row->appointment_time, 0, 8);
                $key = $row->doctor_id.'|'.$row->appointment_date.'|'.$time;

                if (isset($seen[$key])) {
                    return;
                }

                $seen[$key] = true;

                DB::table('appointments')->where('id', $row->id)->update([
                    'active_slot' => $key,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::table('appointment_messages', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['follow_up_on', 'follow_up_instructions']);
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proposed_appointment_id');
            $table->dropColumn(['urgency', 'clinical_question', 'decline_reason']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['active_slot']);
            $table->dropConstrainedForeignId('rescheduled_from_id');
            $table->dropConstrainedForeignId('referral_id');
            $table->dropColumn(['source', 'active_slot']);
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        Schema::dropIfExists('appointment_reschedules');
        Schema::dropIfExists('doctor_availability_exceptions');
        Schema::dropIfExists('clinic_settings');
    }
};
