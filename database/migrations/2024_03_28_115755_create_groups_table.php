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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid_no')->nullable();
            // ->onUpdate('cascade')->onDelete('cascade')
            $table->foreignId('account_id')->references('id')->on('accounts');
            // ->onUpdate('cascade')->onDelete('cascade')
            $table->string('group_name')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
