<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_patients', function (Blueprint $table) {
            $table->string('origin')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('booked_by')->nullable()->constrained('users')->nullOnDelete();
        });

        $firstVisits = DB::table('appointments')
            ->select('id', 'doctor_id', 'patient_id', 'source')
            ->orderBy('id')
            ->get()
            ->unique(fn (object $row) => $row->doctor_id.'-'.$row->patient_id);

        foreach ($firstVisits as $visit) {
            $origin = match ($visit->source) {
                'public', 'patient' => 'booking',
                'receptionist' => 'receptionist',
                'referral' => 'referral',
                'doctor' => 'doctor',
                default => null,
            };

            $existing = DB::table('doctor_patients')
                ->where('doctor_id', $visit->doctor_id)
                ->where('patient_id', $visit->patient_id)
                ->first();

            if ($existing) {
                if ($existing->origin === null && $origin !== null) {
                    DB::table('doctor_patients')->where('id', $existing->id)->update([
                        'origin' => $origin,
                        'updated_at' => now(),
                    ]);
                }

                continue;
            }

            if ($origin === null) {
                continue;
            }

            DB::table('doctor_patients')->insert([
                'doctor_id' => $visit->doctor_id,
                'patient_id' => $visit->patient_id,
                'origin' => $origin,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('doctor_patients')->whereNull('origin')->update([
            'origin' => 'doctor',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booked_by');
        });

        Schema::table('doctor_patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('added_by');
            $table->dropColumn('origin');
        });
    }
};
