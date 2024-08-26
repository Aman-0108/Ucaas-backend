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
        Schema::create('sofia_global_settings', function (Blueprint $table) {
            $table->id();

            $table->string('name')->default('');
            $table->string('value')->default('');
            $table->string('description')->default('');
            $table->boolean('enabled')->default(false);

            $table->bigInteger('created_by')->nullable();

            $table->boolean('isEditable')->default(true);
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
        Schema::dropIfExists('sofia_global_settings');
    }
};
