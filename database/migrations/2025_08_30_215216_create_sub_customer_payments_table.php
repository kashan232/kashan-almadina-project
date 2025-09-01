<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubCustomerPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('sub_customer_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_customer_id');
            $table->date('payment_date');
            $table->decimal('amount', 15,2);
            $table->string('payment_method')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('sub_customer_id')->references('id')->on('sub_customers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_customer_payments');
    }
}
