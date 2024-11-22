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
            $table->enum('stick_agent_type', ['last_spoken', 'longest_time'])->nullable();
            $table->unsignedTinyInteger('stick_agent_expires')->default(1)->comment('Expires are in days minimum is 1 day');
            $table->boolean('sticky_agent_enable')->default(false);

            // Enum column for spam_filter_type
            $table->enum('spam_filter_type', ['1', '2', '3'])->default(null)->comment('1 = robo_calling, 2 = call_screening, 3 = dtmf_input');

            // Enum column for dtmf_type
            $table->enum('dtmf_type', ['random_digit', 'last_caller_id_digit'])->default('random_digit');

            // Enum column for dtmf_length
            $table->enum('dtmf_length', ['1', '2', '3', '4', '5'])->default('2');

            // Enum column for dtmf_retries
            $table->enum('dtmf_retries', ['1', '2', '3'])->default('3');

            // BigInt unsigned for dtmf_retry_file_sound
            $table->unsignedBigInteger('dtmf_retry_file_sound')->nullable()->default(null);


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
