<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDidRateChartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('did_rate_charts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->references('id')->on('did_vendors');
            $table->enum('rate_type',['random','blocks']);
            $table->decimal('rate');
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
        Schema::dropIfExists('did_rate_charts');
    }
}
