<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIvrOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ivr_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ivr_id')->references('id')->on('ivr_masters')->onUpdate('cascade')->onDelete('cascade');
            $table->string('option_key', 255); // varchar(255)
            $table->string('action_name', 255); // varchar(255)
            $table->string('action_id', 120); // varchar(120), nullable if unspecified            
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
        Schema::dropIfExists('ivr_options');
    }
}
