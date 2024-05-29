<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutboundRoutingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outbound_routings', function (Blueprint $table) {
            $table->id();

            $table->string('primary_gateway')->nullable();
            $table->string('aletrnate1_gateway')->nullable();
            $table->string('alternate2_gateway')->nullable();
            $table->string('prefix')->nullable();
            
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
        Schema::dropIfExists('outbound_routings');
    }
}
