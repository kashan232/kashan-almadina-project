<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_acceptance_items', function (Blueprint $row) {
            $row->id();
            $row->unsignedBigInteger('claim_acceptance_id');
            $row->unsignedBigInteger('product_id');
            $row->decimal('quantity', 12, 2)->default(0);
            $row->string('status')->default('Draft');
            $row->timestamps();

            $row->foreign('claim_acceptance_id')->references('id')->on('claim_acceptances')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_acceptance_items');
    }
};
