<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInboundRoutingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inbound_routings', function (Blueprint $table) {
            $table->id();
            
            $table->string('name')->nullable();
            $table->string('destination_number')->nullable();
            $table->enum('action',['', 'ring group','individual', 'queue'])->default('');
            $table->string('type')->nullable();
            $table->enum('other',['', 'voice Mail','hangup recording'])->default('');
            $table->string('caller_id_number_prefix')->nullable();

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
        Schema::dropIfExists('inbound_routings');
    }
}
