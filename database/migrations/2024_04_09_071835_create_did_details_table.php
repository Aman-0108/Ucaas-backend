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
            $table->string('orderid');
            $table->foreignId('did_vendor_id')->references('id')->on('did_vendors');
            $table->string('domain');
            $table->string('did');
            $table->boolean('cnam')->default(false);
            $table->boolean('sms')->default(false);
            $table->boolean('e911')->default(false);
            $table->string('tollfreePrefix')->nullable();
            $table->string('npanxx')->nullable();
            $table->string('ratecenter')->nullable();
            $table->string('thinqTier')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('price', 10, 2);
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
