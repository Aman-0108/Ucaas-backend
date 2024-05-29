<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFollowmesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('followmes', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('account_id')->references('id')->on('accounts')->onUpdate('cascade');
            
            $table->string('destination');
            $table->unsignedInteger('delay');
            $table->unsignedInteger('timeout');
            $table->string('prompt');

            $table->foreignId('extension_id')->references('id')->on('extensions')->onUpdate('cascade')->onDelete('cascade');

            $table->softDeletes();
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
        Schema::dropIfExists('followmes');
    }
}
