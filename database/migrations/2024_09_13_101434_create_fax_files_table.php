<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFaxFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fax_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->references('id')->on('accounts')->onDelete('cascade'); // Foreign key constraint
            $table->string('file_path'); // Path to the file on the server
            $table->string('file_name'); // Original file name
            $table->integer('file_size'); // Size of the file in bytes            
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
        Schema::dropIfExists('fax_files');
    }
}
