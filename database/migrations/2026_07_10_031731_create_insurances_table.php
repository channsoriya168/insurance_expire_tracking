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
        Schema::create('insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_company_id')->constrained()->restrictOnDelete();
            $table->string('policy_no')->unique();
            $table->string('contact_method');
            $table->string('contact_value');
            $table->string('contact_person');
            $table->string('insured_name');
            $table->date('expiry_date');
            $table->foreignId('policy_type_id')->constrained()->restrictOnDelete();
            $table->decimal('sum_insured', 14, 2);
            $table->decimal('premium', 14, 2);
            $table->decimal('net_premium', 14, 2)->default(0);
            $table->decimal('revised_sum_insured', 14, 2);
            $table->decimal('revised_premium', 14, 2);
            $table->decimal('revised_premium_rate', 8, 4);
            $table->date('confirmed_date')->nullable();
            $table->string('status')->default('Pending');
            $table->string('payment_status')->default('Unpaid');
            $table->date('payment_date')->nullable();
            $table->date('policy_received_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('expiry_date');
            $table->index(['status', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurances');
    }
};
