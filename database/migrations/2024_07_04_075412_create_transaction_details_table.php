<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->references('id')->on('payments')->onUpdate('cascade')->onDelete('cascade');
            $table->string('transaction_id');
            $table->decimal('amount_total', 10, 2);
            $table->decimal('amount_subtotal', 10, 2);
            $table->string('transaction_date');
            $table->string('name');
            $table->string('card_number');
            $table->string('exp_month');
            $table->string('exp_year');            
            $table->string('cvc');
            $table->string('fullname');
            $table->string('contact_no');
            $table->string('email');
            $table->string('address');
            $table->string('zip');
            $table->string('city');
            $table->string('state');
            $table->string('country');
            $table->string('description');
            $table->enum('payment_mode', ['card','wallet'])->default('card');
            $table->string('payment_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_details');
    }
}
