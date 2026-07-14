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
        Schema::create('insurance_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_id')->constrained()->cascadeOnDelete();
            $table->string('bucket');
            $table->date('expiry_date');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique('insurance_id');
            $table->index('bucket');
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_notifications');
    }
};
