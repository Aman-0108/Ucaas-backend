<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDestinationRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('destination_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('DestinationId')->references('id')->on('destinations');
            $table->foreignId('RatesTag')->references('id')->on('rates');
            $table->string('RoundingMethod');
            $table->integer('RoundingDecimals');
            $table->decimal('MaxCost', 10, 2); // Adjust precision and scale as needed
            $table->string('MaxCostStrategy');
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
        Schema::dropIfExists('destinationo_rates');
    }
}
