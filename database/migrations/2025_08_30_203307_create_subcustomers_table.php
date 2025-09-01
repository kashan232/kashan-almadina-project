<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('sub_customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id')->unique();
            $table->foreignId('customer_main_id')->constrained('customers')->onDelete('cascade');
            $table->string('customer_name')->nullable();
            $table->string('customer_name_ur')->nullable();
            $table->string('cnic')->nullable();
            $table->string('filer_type')->nullable();
            $table->string('zone')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email_address')->nullable();
            $table->string('contact_person_2')->nullable();
            $table->string('mobile_2')->nullable();
            $table->string('email_address_2')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_customers');
    }
}

