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
        Schema::create('sip_profiles', function (Blueprint $table) {
            $table->id();

            $table->uuid('uid_no')->index()->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();

            $table->string('hostname')->nullable();
            $table->boolean('enabled')->default(false);

            $table->bigInteger('created_by')->nullable();
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
        Schema::dropIfExists('sip_profiles');
    }
};
