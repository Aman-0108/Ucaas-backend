<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDidConfiguresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('did_configures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('did_id')->references('id')->on('did_details')->onUpdate('cascade')->onDelete('cascade');            
            $table->string('usages');
            $table->string('action');
            $table->enum('forward', ['disabled', 'pstn', 'direct']);
            $table->string('forward_to')->nullable();
            $table->boolean('record');
            $table->string('hold_music');
            $table->unsignedTinyInteger('stick_agent_expires')->default(1)->comment('Expires are in days minimum is 1 day');
            $table->boolean('status')->default(false);
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
        Schema::dropIfExists('did_configures');
    }
}
