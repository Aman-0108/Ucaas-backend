<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('created_by');
            $table->foreignId('account_id')->references('id')->on('accounts');
            $table->decimal('amount', 10, 2);
            $table->enum('transaction_type', ['debit', 'credit']);
            $table->string('payment_gateway_session_id')->nullable();
            $table->string('payment_gateway_transaction_id')->index();
            $table->string('payment_gateway')->nullable();
            $table->string('invoice_url')->nullable();
            $table->string('descriptor');            
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
        Schema::dropIfExists('wallet_transactions');
    }
}
