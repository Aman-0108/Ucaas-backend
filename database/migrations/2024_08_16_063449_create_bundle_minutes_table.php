<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBundleMinutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bundle_minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->references('id')->on('accounts')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('available_minutes')->default(0); // Equivalent to int(11) NOT NULL DEFAULT 0
            $table->integer('used_minutes')->default(0); // Equivalent to int(11) NOT NULL DEFAULT 0
            $table->timestamp('expire_time')->nullable(); // Equivalent to timestamp NULL DEFAULT NULL
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
        Schema::dropIfExists('bundle_minutes');
    }
}
