<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDialerMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dialer_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('autodialers_id')->references('id')->on('autodialers')->onUpdate('cascade')->onDelete('cascade');
            $table->string('number', 11);
            $table->unsignedTinyInteger('tries')->default(1);
            $table->enum('status', ['0', '1'])->default('0'); 
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
        Schema::dropIfExists('dialer_members');
    }
}
