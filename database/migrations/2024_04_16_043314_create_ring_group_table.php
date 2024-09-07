<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRingGroupTable extends Migration
{
    /**
     * Run the migrations.
     *$table->uuid('uid_no')->index()->nullable();
     *$table->foreignId('account_id')->references('id')->on('accounts');
     *$table->string('extension')->nullable();
     * $table->enum('voicemailEnabled', ['Y', 'N',])->default('Y')->comment('Y for Yes, N for No');
     *$table->enum('record', ['L', 'I', 'O', 'A', 'D'])->default('A')->comment('L for Local, I for Inbound, O for Outbound, A for All & D for disable');
     *$table->text('description')->nullable();
     *$table->boolean('dnd')->default(false);
     * @return void
     */

    /*
        name , extension , strategy(Enter the extension number.) , timeout_destination(Select the timeout destination for this ring group.) , 
        call_timeout , distinctive_ring , ring_back(Defines what the caller will hear while the destination is being called.) , 
        user_list(Define users assigned to this ring group.) , call_forward (Choose to follow a ring group destination's call forward.), 
        followMe(Choose to follow a ring group destination's follow me.),
        missed_call(Select the notification type, and enter the appropriate destination.) , 
        ring_group_forward(Forward a called Ring Group to an alternate destination.) , context , status , description
    */
    public function up()
    {
        Schema::create('ringgroups', function (Blueprint $table) {
            $table->id();

            $table->integer('account_id');
            $table->string('name')->comment('Enter Ring Group Name Here');
            $table->string('extension')->comment('Enter Extension');
            $table->string('domain_name')->comment('Domain assigned to account_id');
            $table->enum('strategy', ['enterprise', 'sequence', 'simultaneously', 'random', 'rollover'])->default('enterprise');
            $table->string('timeout_destination')->nullable()->comment('Select the timeout destination for this ring group');
            $table->string('call_timeout')->nullable();

            $table->string('ring_group_caller_id_name')->nullable();
            $table->string('ring_group_caller_id_number')->nullable();
            $table->string('ring_group_cid_name_prefix')->nullable();
            $table->string('ring_group_cid_number_prefix')->nullable();

            $table->string('ring_group_timeout_app')->nullable();
            $table->string('ring_group_timeout_data')->nullable();

            $table->string('distinctive_ring')->nullable();

            $table->string('ring_back')->nullable()->comment('Defines what the caller will hear while the destination is being called.');
            $table->boolean('followme')->nullable()->comment('Choose to follow a ring group destinations follow me');
            $table->string('missed_call')->nullable()->comment('Select the notification type, and enter the appropriate destination');

            $table->string('missed_destination')->nullable()->comment('destination email if have');
            $table->string('ring_group_forward')->nullable()->comment('Forward a called Ring Group to an alternate destination');
            $table->string('ring_group_forward_destination')->nullable()->comment('ring_group_forward_destination destination');
            $table->string('toll_allow')->nullable();

            $table->string('context')->nullable();
            $table->string('greeting')->nullable();

            $table->integer('created_by');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->boolean('recording_enabled')->default(false)->comment('0 for disable, 1 for enable');

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
        Schema::dropIfExists('ring_group');
    }
}
