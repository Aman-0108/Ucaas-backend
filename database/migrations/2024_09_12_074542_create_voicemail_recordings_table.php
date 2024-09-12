<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVoicemailRecordingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voicemail_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->references('id')->on('accounts')->onUpdate('cascade')->onDelete('cascade');
            $table->string('src')->nullable();  
            $table->string('dest')->nullable();  
            $table->string('recording_path');
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
        Schema::dropIfExists('voicemail_recordings');
    }
}
