<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRingGroupDestinations extends Migration
{
    /**
     * Run the migrations.
     * id , uid , ring_group_id , account_id ,  destination , delay_order , prompt , status
     * @return void
     */
    public function up()
    {
        Schema::create('ring_group_destinations', function (Blueprint $table) {
            $table->id()->index();
            $table->integer('account_id')->index();
            $table->foreignId('ring_group_id')->references('id')->on('ringgroups')->onUpdate('cascade')->onDelete('cascade');
            $table->string('destination')->comment('Enter the destination of this ring group');
            
            $table->string('destination_timeout')->nullable();
             
            $table->integer('delay_order');
            $table->string('prompt')->nullable();
            $table->integer('created_by');
            $table->enum('status',['active','inactive'])->default('active');
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
        Schema::dropIfExists('posts_ring_group_destinations');
    }
}
