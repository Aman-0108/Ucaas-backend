<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIvrMastersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ivr_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->references('id')->on('accounts')->onUpdate('cascade')->onDelete('cascade');
            $table->string('ivr_name', 255); // varchar(255)
            $table->enum('ivr_type', ['1', '0'])->default('1'); // enum type with default value
            $table->string('greet_long', 255)->nullable(); // long greeting
            $table->string('greet_short', 255)->nullable(); // short greeting
            $table->string('invalid_sound', 255)->nullable(); // optional sound
            $table->string('exit_sound', 255)->nullable(); // optional exit sound
            $table->string('confirm_macro', 255)->nullable(); // optional macro
            $table->string('confirm_key', 255)->nullable(); // optional confirm key
            $table->string('tts_engine', 255)->nullable(); // optional TTS engine
            $table->string('tts_voice', 255)->nullable(); // optional TTS voice
            $table->integer('confirm_attempts')->nullable()->default(3); // confirm attempts
            $table->integer('timeout')->nullable()->default(10000); // timeout in milliseconds
            $table->integer('inter_digit_timeout')->nullable()->default(2000); // inter-digit timeout
            $table->integer('max_failures')->nullable()->default(3); // max failures
            $table->integer('max_timeouts')->nullable()->default(3); // max timeouts
            $table->integer('digit_len')->nullable(); // digit length, unspecified type
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
        Schema::dropIfExists('ivr_masters');
    }
}
