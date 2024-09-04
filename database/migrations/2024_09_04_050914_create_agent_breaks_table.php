<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgentBreaksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agent_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_center_agent_id')->references('id')->on('call_center_agents')->onUpdate('cascade')->onDelete('cascade');  
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('total_break_time')->default(0);
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
        Schema::dropIfExists('agent_breaks');
    }
}
