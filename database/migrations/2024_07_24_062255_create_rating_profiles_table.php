<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rating_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('Tenant');
            $table->enum('Category', ['call', 'sms', 'data', 'custom'])->default('call');
            $table->string('Subject');
            $table->timestamp('ActivationTime')->nullable();
            $table->foreignId('RatingPlanId')->references('id')->on('rating_plans')->onUpdate('cascade')->onDelete('cascade');
            $table->string('RatesFallbackSubject')->nullable();
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
        Schema::dropIfExists('rateing_profiles');
    }
}
