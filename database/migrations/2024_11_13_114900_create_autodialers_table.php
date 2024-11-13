<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutodialersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('autodialers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('account_id')->references('id')->on('accounts');
            $table->unsignedTinyInteger('tries')->default(1);
            $table->enum('status', ['0', '1'])->default('0'); 
            $table->timestamp('schedule_time')->nullable();
            $table->string('instance_id', 120)->nullable();
            $table->foreignId('did_configure_id')->nullable()->references('id')->on('did_configures');
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
        Schema::dropIfExists('autodialers');
    }
}
