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
            $table->uuid('uid_no')->index()->nullable();
            // ->onUpdate('cascade')->onDelete('cascade')
            $table->foreignId('account_id')->references('id')->on('accounts');
            // ->onUpdate('cascade')->onDelete('cascade')
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->ipAddress('proxy')->nullable();
            $table->bigInteger('expireseconds')->nullable();
            $table->string('register')->nullable();
            $table->string('profile')->nullable();
            $table->string('fromUser')->nullable();
            $table->string('fromDomain')->nullable();
            $table->string('realm')->nullable();
            $table->enum('status', ['E', 'D'])->default('D')->comment('E for Enable & D for Disable');
            $table->text('description')->nullable();
            $table->bigInteger('retry')->nullable();
            $table->softDeletes();
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
