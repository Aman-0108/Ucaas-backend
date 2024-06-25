<?php

use App\Enums\AgentState;
use App\Enums\AgentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallCenterAgentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_center_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_center_queue_id')->references('id')->on('call_center_queues')->onUpdate('cascade')->onDelete('cascade');
            
            $table->string('agent_name')->nullable();
            $table->string('password');

            $table->enum('type', config('enums.agent.type'))->nullable();
            $table->string('contact')->nullable();
            $table->unsignedInteger('max_no_answer')->nullable();
            $table->unsignedBigInteger('wrap_up_time')->nullable();
            $table->unsignedBigInteger('reject_delay_time')->nullable();

            $table->unsignedBigInteger('busy_delay_time')->nullable();
            $table->unsignedBigInteger('no_answer_delay_time')->nullable();
            $table->boolean('reserve-agents')->default(false);
            $table->boolean('truncate-agents-on-load')->default(false);
            $table->boolean('truncate-tiers-on-load')->default(false);

            $table->string('tier_level')->nullable();
            $table->string('tier_position')->nullable();
            $table->enum('status', config('enums.agent.status'))->nullable();
            $table->enum('state', config('enums.agent.state'))->nullable();
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
        Schema::dropIfExists('call_center_agents');
    }
}
