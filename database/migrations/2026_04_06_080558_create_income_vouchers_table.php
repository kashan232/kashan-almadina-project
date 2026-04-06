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
        Schema::create('income_vouchers', function (Blueprint $table) {
            $table->id();

            // Header fields (The destination account where money is coming IN)
            $table->string('ivid')->nullable();          // Income Voucher ID
            $table->date('entry_date')->nullable();     // Entry Date
            $table->string('account_head')->nullable(); // Account Head (Header)
            $table->string('account_id')->nullable();   // Account ID (Header)
            $table->text('remarks')->nullable();        // Remarks

            // Row-wise data (The source parties who are PAYING)
            $table->text('narration_id')->nullable();   // JSON array of narration IDs
            $table->text('party_type')->nullable();     // JSON array of types (vendor/customer/walkin/account)
            $table->text('party_id')->nullable();       // JSON array of IDs
            $table->text('reference_no')->nullable();   // JSON array of references
            $table->text('amount')->nullable();         // JSON array of individual amounts
            
            // Footer total
            $table->decimal('total_amount', 15, 2)->nullable();
            
            $table->string('status')->default('draft'); // draft, posted
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_vouchers');
    }
};
