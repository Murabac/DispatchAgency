<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('bank_details')->nullable();
            $table->string('invoice_prefix')->default('INV-');
            $table->string('quotation_prefix')->default('QUO-');
            $table->string('receipt_prefix')->default('RCT-');
            $table->unsignedInteger('next_invoice_number')->default(1);
            $table->unsignedInteger('next_quotation_number')->default(1);
            $table->unsignedInteger('next_receipt_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
