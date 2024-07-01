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
        Schema::create('subscribes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->foreignId()->index();
            $table->uuid('course_id')->foreignId()->index();
            $table->string('ref_id');
            $table->string('access_code')->default('0');
            $table->string('reference')->default('0');
            $table->string('scholarship');
            $table->string('course_type');
            $table->string('bp_rate');
            $table->string('payment_amount');
            $table->string('prove_of_payment')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('adminConsent')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('subscribes');
    }
};
