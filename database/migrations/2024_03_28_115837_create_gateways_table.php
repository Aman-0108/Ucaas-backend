<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();  
            $table->foreignId('account_id')->nullable()->references('id')->on('accounts');          
            $table->string('name');
            $table->string('username');
            $table->string('password');
            $table->ipAddress('proxy');
            $table->bigInteger('expireseconds')->nullable();
            $table->string('register')->nullable();
            $table->string('profile')->nullable();
            $table->string('fromUser')->nullable();
            $table->string('fromDomain')->nullable();
            $table->string('realm')->nullable();
            $table->enum('status', ['E', 'D'])->default('D')->comment('E for Enable & D for Disable');
            $table->text('description')->nullable();
            $table->bigInteger('retry')->nullable();
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateways');
    }
};
