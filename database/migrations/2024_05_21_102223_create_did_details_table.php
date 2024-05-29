<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDidDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('did_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->references('id')->on('accounts');
            $table->string('transaction_id')->index();
            $table->foreign('transaction_id')->references('transaction_id')->on('payments');
            $table->string('domain');
            $table->string('did');
            $table->string('didSummary');
            $table->string('tollfreePrefix');
            $table->string('npanxx');
            $table->string('ratecenter');
            $table->string('thinqTier');
            $table->string('currency');
            $table->string('price');
            $table->integer('created_by');
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
        Schema::dropIfExists('did_details');
    }
}
