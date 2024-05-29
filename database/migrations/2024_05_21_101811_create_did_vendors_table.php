<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDidVendorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('did_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_name')->unique();
            $table->string('username')->nullable();
            $table->string('token')->nullable();
            $table->enum('status',['active','inactive'])->default('inactive');
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
        Schema::dropIfExists('did_vendors');
    }
}
