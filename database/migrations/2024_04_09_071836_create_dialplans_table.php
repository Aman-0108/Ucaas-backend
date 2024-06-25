<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDialplansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dialplans', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['Local', 'Inbound', 'Outbound'])->nullable();
            $table->string('country_code');
            $table->string('destination');
            $table->string('context')->nullable();
            $table->string('dial_action')->nullable()->comment('select from dropdown[in drop down there will have single voip account/extension and as well group]');
            $table->string('caller_Id_name')->nullable();
            $table->string('caller_Id_number')->nullable();
            $table->string('caller_Id_name_prefix')->nullable()->comment('set a prefix on a caller id name');
            $table->enum('usage', ['voice', 'fax', 'text', 'emergency'])->default('voice')->comment('Set how the Destination will be used');
            $table->string('domain');
            $table->string('order')->nullable();
            $table->boolean('destination_status')->default(false);
            $table->string('description')->nullable();

            $table->integer('account_id');
            $table->integer('user')->comment('assign the destination to a user')->nullable();
            $table->integer('group')->comment('assign the destination to a group')->nullable();
            $table->string('record')->comment('save the recording')->nullable();
            $table->string('holdMusic')->nullable();
             
            $table->longText('dialplan_xml')->default("");
            $table->boolean('dialplan_enabled')->default(false);
            
            $table->text('hostname')->default("");           

            $table->bigInteger('created_by')->nullable();
            $table->string('action')->nullable();

            $table->foreignId('call_center_queues_id')->nullable()->references('id')->on('call_center_queues');

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
        Schema::dropIfExists('dialplans');
    }
}
