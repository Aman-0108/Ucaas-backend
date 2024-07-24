<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rating_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('DestinationRatesId')->references('id')->on('destination_rates');
            $table->string('TimingTag');
            $table->integer('Weight');
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
        Schema::dropIfExists('rateing_plans');
    }
}
