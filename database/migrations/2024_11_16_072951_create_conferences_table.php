<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conferences', function (Blueprint $table) {
            $table->id();
            $table->integer('room_id');
            $table->string('instance_id', 255)->nullable(); // varchar(255)
            $table->string('conf_ext', 10)->nullable(); // varchar(10)
            $table->foreignId('account_id')->nullable()->references('id')->on('accounts')->onUpdate('cascade')->onDelete('cascade');
            $table->string('conf_name', 255); // varchar(255)
            $table->integer('moh_sound'); // int(11)
            $table->text('description')->nullable(); // text
            $table->string('participate_pin', 11)->nullable(); // varchar(11)
            $table->integer('conf_max_members'); // int(11)
            $table->tinyInteger('pin_retries')->default(3); // tinyint(3)
            $table->enum('nopin', ['0', '1'])->default('0'); // enum('0', '1')
            $table->string('moderator_pin', 11)->nullable(); // varchar(11)
            $table->enum('wait_moderator', ['0', '1'])->default('0'); // enum('0', '1')
            $table->enum('name_announce', ['0', '1'])->default('0'); // enum('0', '1')
            $table->enum('end_conf', ['0', '1'])->default('0'); // enum('0', '1')
            $table->enum('record_conf', ['0', '1'])->default('0'); // enum('0', '1')
            $table->enum('status', ['0', '1'])->default('1'); // enum('0', '1')
            $table->dateTime('conf_start_time')->nullable(); // datetime
            $table->dateTime('conf_end_time')->nullable(); // datetime
            $table->longText('notification_settings')->nullable(); // longtext
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
        Schema::dropIfExists('conferences');
    }
}
