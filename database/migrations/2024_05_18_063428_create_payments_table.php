<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->references('id')->on('accounts');
            $table->foreignId('card_id')->nullable()->references('id')->on('card_details')->onUpdate('cascade')->onDelete('cascade');  
            $table->foreignId('billing_address_id')->nullable()->references('id')->on('billing_addresses')->onUpdate('cascade')->onDelete('cascade');    
            $table->decimal('amount_total', 10, 2)->nullable();
            $table->decimal('amount_subtotal', 10, 2)->nullable();
            $table->string('stripe_session_id')->nullable();
            $table->string('transaction_id')->index();
            $table->string('payment_gateway')->nullable();
            $table->string('transaction_type')->nullable();
            $table->string('invoice_url')->nullable();
            $table->string('subscription_type')->nullable();            
            $table->string('payment_method_options')->nullable();
            $table->string('currency')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('transaction_date')->nullable();
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
        Schema::dropIfExists('payments');
    }
}
