<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_details', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('account_id')->references('id')->on('accounts');            
            $table->foreignId('document_id')->references('id')->on('documents');
            $table->string('path');
            $table->enum('status', ['1','2','3'])->comment('1 for approved and 2 for rejected & 3 for uploaded');
            $table->foreignId('status_by')->nullable()->references('id')->on('users');
            $table->text('description')->nullable();
         
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
        Schema::dropIfExists('account_details');
    }
}
