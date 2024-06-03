<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {  
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->string('company_name');
            $table->string('admin_name');
            $table->foreignId('timezone_id')->references('id')->on('timezones');
            $table->string('email')->unique();
            $table->string('contact_no');
            $table->string('alternate_contact_no')->nullable();

            $table->string('unit')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();
            $table->string('country')->nullable();

            $table->enum('company_status',['new','applied','approved']);
            $table->enum('status',['active','inactive'])->default('active');

            $table->foreignId('package_id')->references('id')->on('packages');

            $table->string('passkey')->nullable();
            $table->integer('approved_by')->nullable();

            $table->string('temp_password')->nullable();
            $table->string('payment_url')->nullable();
            $table->text('firebase_token')->nullable();

            $table->softDeletes();
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
        Schema::dropIfExists('accounts');
    }
}
