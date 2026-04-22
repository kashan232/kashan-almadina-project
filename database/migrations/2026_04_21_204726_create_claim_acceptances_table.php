<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claim_acceptances', function (Blueprint $row) {
            $row->id();
            $row->string('voucher_no')->unique();
            $row->string('btr_no')->nullable();
            $row->date('date');
            $row->string('party_type');
            $row->unsignedBigInteger('party_id');
            $row->text('remarks')->nullable();
            $row->string('status')->default('Draft');
            $row->unsignedBigInteger('created_by');
            $row->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_acceptances');
    }
};
