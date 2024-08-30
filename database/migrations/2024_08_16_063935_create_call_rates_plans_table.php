<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallRatesPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_rates_plans', function (Blueprint $table) {
            $table->id();
            $table->string('destination_name', 150)->default('default');
            $table->foreignId('account_id')->nullable()->references('id')->on('accounts')->onUpdate('cascade')->onDelete('cascade');
            $table->string('destination', 5)->comment('(*) can dial any destination');
            $table->smallInteger('selling_billing_block');
            $table->decimal('sell_rate', 10, 2);
            $table->decimal('buy_rate', 10, 2);
            $table->integer('gateway_id')->unsigned();
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
        Schema::dropIfExists('call_rates_plans');
    }
}
