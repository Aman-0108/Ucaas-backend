<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faxes', function (Blueprint $table) {
            $table->id();
            $table->string('fax_files_id')->references('id')->on('fax_files')->onDelete('cascade');
            $table->string('sender'); // Sender's fax number or email
            $table->string('recipient'); // Recipient's fax number
            $table->string('subject')->nullable(); // Subject of the fax (if applicable)
            $table->enum('status', ['pending', 'sent', 'failed', 'received'])->default('pending'); // Status of the fax
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
        Schema::dropIfExists('faxes');
    }
}
