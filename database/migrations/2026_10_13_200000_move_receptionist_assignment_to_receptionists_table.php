<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('receptionists')) {
            Schema::create('receptionists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('users', 'assigned_doctor_id')) {
            return;
        }

        $assigned = DB::table('users')
            ->where('role', 'receptionist')
            ->whereNotNull('assigned_doctor_id')
            ->get(['id', 'assigned_doctor_id']);

        foreach ($assigned as $row) {
            DB::table('receptionists')->updateOrInsert(
                ['user_id' => $row->id],
                [
                    'doctor_id' => $row->assigned_doctor_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_doctor_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'assigned_doctor_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('assigned_doctor_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('doctors')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('receptionists')) {
            return;
        }

        foreach (DB::table('receptionists')->get(['user_id', 'doctor_id']) as $row) {
            DB::table('users')->where('id', $row->user_id)->update([
                'assigned_doctor_id' => $row->doctor_id,
            ]);
        }
    }
};
