<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->index()->unique();
            $table->foreignId('sender_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade'); 
            $table->foreignId('user_id')->nullable(); // User who received the message
            $table->enum('message_type', ['text/plain', 'image', 'video', 'file'])->default('text/plain'); // Type of message
            $table->text('message_text')->nullable(); // Content of the message
            $table->string('attachment_url')->nullable(); // URL for attachment
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
        Schema::dropIfExists('messages');
    }
}
