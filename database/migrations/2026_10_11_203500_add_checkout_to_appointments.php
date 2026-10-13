<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->time('ending_time')->nullable()->after('appointment_time');
            $table->decimal('ticket_price', 10, 2)->nullable()->after('visit_type');
            $table->string('payment_status')->nullable()->after('ticket_price');
            $table->string('stripe_session_id')->nullable()->unique()->after('payment_status');
            $table->string('stripe_payment_intent')->nullable()->after('stripe_session_id');
            $table->timestamp('paid_at')->nullable()->after('stripe_payment_intent');
        });

        Schema::create('appointment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('group_name');
            $table->string('path');
            $table->string('original_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_reports');

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['stripe_session_id']);
            $table->dropColumn([
                'ending_time',
                'ticket_price',
                'payment_status',
                'stripe_session_id',
                'stripe_payment_intent',
                'paid_at',
            ]);
        });
    }
};
