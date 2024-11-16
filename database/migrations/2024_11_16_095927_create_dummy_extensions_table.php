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
            $table->foreignId('account_id')->references('id')->on('accounts')->onUpdate('cascade')->onDelete('cascade'); // Account ID, unsigned integer
            $table->foreignId('conference_id')->references('id')->on('conferences')->onUpdate('cascade')->onDelete('cascade'); // Conference ID, unsigned integer
            $table->string('extension', 150); // Extension (varchar(150))
            $table->string('password', 150); // Password (varchar(150))
            $table->enum('status', ['1', '0'])->default('0'); // Enum for status
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
