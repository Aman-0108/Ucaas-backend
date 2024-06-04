<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            
            $table->string('company_name');
            
            $table->string('admin_name');
            $table->foreignId('timezone_id')->references('id')->on('timezones');
            $table->string('email');
            $table->string('contact_no');
            $table->string('alternate_contact_no')->nullable();

            $table->string('building')->nullable();
            $table->string('unit');
            $table->string('street');
            $table->string('city');
            $table->string('state');
            $table->string('zip');
            $table->string('country');

            $table->foreignId('package_id')->references('id')->on('packages');

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
        Schema::dropIfExists('leads');
    }
}
