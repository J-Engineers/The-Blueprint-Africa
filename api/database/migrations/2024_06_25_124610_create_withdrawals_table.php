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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id')->foreignId()->index();
            $table->string('payment_details_id')->foreignId()->index()->nullable();
            $table->integer('otp');
            $table->string('bp')->default(0);
            $table->string('rate')->default(0);
            $table->string('description')->nullable();
            $table->integer('userConsent')->nullable();
            $table->integer('adminConsent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
