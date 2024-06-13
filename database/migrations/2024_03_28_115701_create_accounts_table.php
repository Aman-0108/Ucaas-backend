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

            $table->string('building')->nullable();
            $table->string('unit')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('state');
            $table->string('zip')->nullable();
            $table->string('country')->nullable();

            $table->enum('company_status', config('enums.company.company_status'))->comment(config('enums.company.comment_company_status'));
            $table->enum('status', config('enums.company.status'))->default(config('enums.company.default_status'));

            $table->foreignId('package_id')->references('id')->on('packages');

            $table->foreignId('payment_approved_by')->nullable()->references('id')->on('users');
            $table->integer('document_approved_by')->nullable()->references('id')->on('users');

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
