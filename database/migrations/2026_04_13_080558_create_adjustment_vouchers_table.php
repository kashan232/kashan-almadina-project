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
        Schema::create('adjustment_vouchers', function (Blueprint $table) {
            $table->id();

            // Header fields (The source party where money is coming FROM)
            $table->string('avid')->nullable();          // Adjustment Voucher ID
            $table->date('entry_date')->nullable();     // Entry Date
            $table->string('party_type')->nullable();   // Party Type (Header)
            $table->string('party_id')->nullable();     // Party ID (Header)
            $table->text('remarks')->nullable();        // Remarks

            // Row-wise data (The destination accounts where money is going TO)
            $table->text('narration_id')->nullable();   // JSON array of narration IDs
            $table->text('account_head')->nullable();   // JSON array of heads (Header in income-voucher)
            $table->text('account_id')->nullable();     // JSON array of accounts (Header in income-voucher)
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
        Schema::dropIfExists('adjustment_vouchers');
    }
};
