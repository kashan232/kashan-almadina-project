<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_customer_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_customer_id')->constrained('sub_customers')->onDelete('cascade');
            $table->foreignId('admin_or_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('previous_balance', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_customer_ledgers');
    }
};
