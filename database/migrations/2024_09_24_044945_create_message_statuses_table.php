<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateMessageStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('message_uuid')->index(); // Make sure it's a string
            $table->foreign('message_uuid')->references('uuid')->on('messages')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->nullable(); // User who received the message
            $table->string('receiver_id');
            // $table->foreignId('receiver_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('status', ['sent', 'delivered', 'read', 'accepted', 'rejected'])->default('sent');
            $table->timestamp('created_at')->useCurrent(); // Sets current timestamp on insert
            $table->timestamp('updated_at')->useCurrent()->nullable()->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_statuses');
    }
}
