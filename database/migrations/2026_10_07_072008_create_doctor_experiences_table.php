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
        Schema::create('doctor_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('designation');

            $table->string('hospital_name');

            $table->string('location')
                ->nullable();

            $table->date('start_date');

            $table->date('end_date')
                ->nullable();

            $table->boolean('is_current')
                ->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_experiences');
    }
};
