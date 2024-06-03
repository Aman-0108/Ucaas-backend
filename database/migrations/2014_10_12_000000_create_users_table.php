<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('contact')->nullable();
            $table->integer('group_id')->nullable();
            $table->string('domain_id')->nullable();
            $table->string('apiKey')->nullable();

            $table->bigInteger('account_id')->nullable();

            $table->integer('timezone_id')->nullable();
            $table->enum('language', config('enums.languages'))->default('English');
            $table->enum('status', config('enums.user.status'))->default(config('enums.user.defaultstatus'))->comment(config('enums.user.statuscomment'));

            $table->enum('usertype', config('enums.user.types'))->default(config('enums.user.defaultusertype'));
            $table->bigInteger('extension_id')->nullable();     
            
            $table->bigInteger('socket_session_id')->nullable();
            $table->enum('socket_status',config('enums.socket.status'))->default(config('enums.socket.defaultstatus'));

            $table->integer('approved_by')->nullable();
            $table->text('firebase_token')->nullable();            

            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};
