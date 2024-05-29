<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManageExtensionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manage_extensions', function (Blueprint $table) {
            $table->id();
            $table->enum('filetype', ['Listen', 'Download', 'Audio'])->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->string('action')->nullable();
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
        Schema::dropIfExists('manage_extensions');
    }
}
