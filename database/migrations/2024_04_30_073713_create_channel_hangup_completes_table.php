<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateChannelHangupCompletesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_hangup_completes', function (Blueprint $table) {
            $table->id();

            $table->string('Call-Direction')->nullable();

            $table->string('fs_call_uuid')->nullable();

            $table->string('Hangup-Cause')->nullable();
            $table->string('variable_DIALSTATUS')->nullable();
            $table->string('Caller-Orig-Caller-ID-Name')->nullable();
            $table->string('Caller-Orig-Caller-ID-Number')->nullable();
            $table->string('Channel-Call-State')->nullable();

            $table->string('Caller-Caller-ID-Number')->nullable();
            $table->string('Caller-Callee-ID-Number')->nullable();

            $table->string('variable_sip_from_user')->nullable();
            $table->string('variable_sip_to_user')->nullable();
            $table->ipAddress('variable_sip_from_host')->nullable();
            $table->ipAddress('variable_sip_to_host')->nullable();

            $table->string('variable_sip_call_id')->nullable();

            $table->timestamp('variable_start_stamp')->nullable();
            $table->timestamp('variable_end_stamp')->nullable();
            $table->timestamp('variable_answer_stamp')->nullable();

            $table->unsignedInteger('variable_duration')->nullable();  

            $table->unsignedInteger('variable_billsec')->nullable();
            $table->unsignedInteger('variable_billmsec')->nullable();

            $table->unsignedInteger('variable_answersec')->nullable();
            $table->unsignedInteger('variable_answermsec')->nullable();
            
            $table->unsignedInteger('variable_waitsec')->nullable();
            $table->unsignedInteger('variable_waitmsec')->nullable();

            $table->unsignedInteger('variable_progresssec')->nullable();
            $table->unsignedInteger('variable_progressmsec')->nullable();

            $table->decimal('variable_nibble_total_billed', 10, 2)->nullable();
            $table->decimal('variable_nibble_current_balance', 10, 2)->nullable();


            $table->string('variable_record_stereo')->nullable();
            $table->unsignedInteger('variable_mduration')->nullable();   
            $table->unsignedInteger('variable_progress_mediasec')->nullable();         
            $table->string('variable_rtp_audio_in_quality_percentage')->nullable();
            

            $table->ipAddress('Caller-Network-Addr')->nullable();
            $table->ipAddress('Other-Leg-Network-Addr')->nullable();

            $table->string('variable_dialed_extension')->nullable();

            $table->bigInteger('caller_user_id')->nullable();
            $table->bigInteger('callee_user_id')->nullable();

            $table->bigInteger('account_id')->nullable();

            $table->foreignId('user_id')->nullable()->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');

            $table->string('recording_path')->nullable();

            $table->string('application_state')->nullable();
            $table->string('application_state_to')->nullable();

            $table->softDeletes();

            // $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('channel_hangup_completes');
    }
}
