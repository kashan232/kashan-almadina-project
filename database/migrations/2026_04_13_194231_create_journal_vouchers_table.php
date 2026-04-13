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
        Schema::create('journal_vouchers', function (Blueprint $table) {
            $table->id();

            // Header fields
            $table->string('jvid')->nullable();          // Journal Voucher ID
            $table->date('entry_date')->nullable();      // Entry Date
            $table->string('party_type')->nullable();    // Header Party Type
            $table->string('party_id')->nullable();      // Header Party ID
            $table->string('reference_no')->nullable();  // Main Reference
            $table->text('remarks')->nullable();         // General Remarks

            // Row-wise data (JSON arrays)
            $table->text('narration_id')->nullable();    // JSON array of narration IDs
            $table->text('account_id')->nullable();      // JSON array of account IDs
            $table->text('debit')->nullable();           // JSON array of debits
            $table->text('credit')->nullable();          // JSON array of credits
            
            // Footer totals
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            
            $table->string('status')->default('draft'); // draft, posted
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_vouchers');
    }
};
