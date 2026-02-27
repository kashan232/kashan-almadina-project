<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update enum to include Unposted and Posted values
        DB::statement("ALTER TABLE `inward_gatepasses` MODIFY `status` ENUM('pending','linked','cancelled','Unposted','Posted') NOT NULL DEFAULT 'Unposted'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `inward_gatepasses` MODIFY `status` ENUM('pending','linked','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
