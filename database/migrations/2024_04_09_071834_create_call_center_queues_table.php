<?php

use App\Enums\Strategy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallCenterQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_center_queues', function (Blueprint $table) {
            $table->id();

            $table->foreignId('account_id')->references('id')->on('accounts');

            $table->string('queue_name')->nullable();
            $table->string('greeting')->nullable();
            $table->string('extension');
            $table->enum('strategy', config('enums.agent.strategy'))->nullable();

            $table->string('moh_sound')->nullable();
            $table->boolean('record_template')->default(false);

            $table->enum('time_base_score',['queue','system'])->default('queue')->comment('If set to system, it will add the number of seconds since the call was originally answered (or entered the system) to the callers base score');
            $table->boolean('tier_rules_apply')->default(false)->comment(' when a caller advances through a queues tiers. If False, they will use all tiers with no wait.');   
            $table->unsignedBigInteger('tier_rule_wait_second')->nullable()->comment('The time in seconds that a caller is required to wait before advancing to the next tier.');
            $table->boolean('tier_rule_wait_multiply_level')->default(false)->comment('If False, then once tier-rule-wait-second is passed, the caller is offered to all tiers in order (level/position). If True, the tier-rule-wait-second will be multiplied by the tier level and the caller will have to wait on every tier **tier-rule-wait-second**s before advancing to the next tier');
            $table->boolean('tier_rule_no_agent_no_wait')->default()->comment('If True, callers will skip tiers that dont have agents available. Otherwise, they are be required to wait before advancing. Agents must be logged off to be considered not available.');
            $table->boolean('abandoned_resume_allowed')->default(false)->comment('If True, a caller who has abandoned the queue can re-enter and resume their previous position in that queue. In order to maintain their position in the queue, they must not abandoned it for longer than the number of seconds defined in discard-abandoned-after');
            $table->unsignedBigInteger('max_wait_time')->default(0);
            $table->unsignedBigInteger('max_wait_time_with_no_agent')->default(0);
            $table->unsignedBigInteger('max_wait_time_with_no_agent_time_reached')->default(5);
            $table->unsignedBigInteger('ring_progressively_delay')->default(10);

            $table->string('queue_timeout_action')->nullable();
            $table->unsignedBigInteger('discard_abandoned_after')->nullable();
            $table->string('queue_cid_prefix')->nullable();

            $table->foreignId('created_by')->references('id')->on('users');
            $table->boolean('recording_enabled')->default(false)->comment('0 for disable, 1 for enable');
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
        Schema::dropIfExists('call_center_queues');
    }
}
