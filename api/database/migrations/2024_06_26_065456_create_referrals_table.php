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
        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->foreignId()->index();
            $table->string('total_bp')->default('0');
            $table->string('used_bp')->default('0');
            $table->string('withdrawn_bp')->default('0');
            $table->string('access_code')->default('0');
            $table->string('reference')->default('0');
            $table->string('ref_id')->default('0');
            $table->string('payment_status')->default('0');
            $table->string('payment_mode')->default('0');
            $table->string('payment_amount')->default('0');
            $table->string('prove_of_payment')->default('0');
            $table->string('adminConsent')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
