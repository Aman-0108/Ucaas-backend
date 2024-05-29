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
        Schema::create('uids', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid_no')->index()->nullable();
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->timestamp('server_timezone')->nullable();
            $table->bigInteger('user_id')->nullable();   
            $table->string('action')->nullable();          
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uids');
    }
};
