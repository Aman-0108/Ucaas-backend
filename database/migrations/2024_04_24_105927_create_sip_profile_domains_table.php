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
        Schema::create('sip_profile_domains', function (Blueprint $table) {
            $table->id();

            $table->uuid('uid_no')->index()->nullable();
            $table->string('sip_profile_id')->nullable();

            $table->string('name')->nullable();
            $table->string('alias')->nullable();
            $table->string('parse')->nullable();
        
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
        Schema::dropIfExists('sip_profile_domains');
    }
};
