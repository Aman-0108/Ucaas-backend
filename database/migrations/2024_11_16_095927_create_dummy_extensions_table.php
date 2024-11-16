<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDummyExtensionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dummy_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_id')->references('id')->on('conferences')->onUpdate('cascade')->onDelete('cascade'); // Conference ID, unsigned integer
            $table->foreignId('account_id')->references('id')->on('accounts');

            $table->string('domain');
            $table->string('extension');
            $table->string('password');
            $table->string('voicemail_password');

            $table->bigInteger('user')->nullable();
            $table->tinyInteger('range')->nullable();            
            $table->string('account_code')->nullable();
            $table->string('effectiveCallerIdName')->nullable();
            $table->string('effectiveCallerIdNumber')->nullable();
            $table->string('outbundCallerIdName')->nullable();
            $table->string('outbundCallerIdNumber')->nullable();

            $table->string('emergencyCallerIdName')->nullable();
            $table->string('emergencyCallerIdNumber')->nullable();
            $table->string('directoryFullname')->nullable();
            $table->string('directoryVisible')->nullable();
            $table->string('directoryExtensionVisible')->nullable();
            $table->string('maxRegistration')->nullable();
            $table->string('limitMax')->nullable();
            $table->string('limitDestinations')->nullable();

            $table->enum('voicemailEnabled', ['Y', 'N',])->default('Y')->comment('Y for Yes, N for No');
            $table->string('voiceEmailTo')->nullable();
            $table->string('voiceMailFile')->nullable();
            $table->string('voiceMailkeepFile')->nullable();
            $table->string('missedCall')->nullable();
            $table->string('tollAllowValue')->nullable();
            $table->bigInteger('callTimeOut')->nullable()->comment('Enter the ring time (delay in seconds) before sending a call to voicemail.');
            $table->string('callgroup')->nullable();
            $table->enum('callScreen', ['Enable', 'Disable',])->default('Enable');

            $table->enum('record', ['L', 'I', 'O', 'A', 'D'])->default('A')->comment('L for Local, I for Inbound, O for Outbound, A for All & D for disable');            
            $table->text('description')->nullable();
            $table->boolean('callforward')->default(false);
            $table->string('callforwardTo')->nullable();
            $table->boolean('onbusy')->default(false);
            $table->string('onbusyTo')->nullable();
            $table->boolean('noanswer')->default(false);
            $table->string('noanswerTo')->nullable();
            $table->boolean('notregistered')->default(false);
            $table->string('notregisteredTo')->nullable();
            $table->boolean('dnd')->default(false);
            $table->boolean('followme')->default(false);
            $table->boolean('ignorebusy')->default(false);

            $table->boolean('blockIncomingStatus')->default(false);
            $table->boolean('blockOutGoingStatus')->default(false);

            $table->bigInteger('created_by')->nullable();

            $table->boolean('sofia_status')->default(false);

            $table->foreignId('moh_sound')->nullable()->references('id')->on('sounds');
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
        Schema::dropIfExists('dummy_extensions');
    }
}
