<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->index();
            $table->foreign('transaction_id')->references('transaction_id')->on('payments');
            
            $table->foreignId('account_id')->references('id')->on('accounts');
            $table->foreignId('package_id')->references('id')->on('packages');
            
            $table->timestamp('start_date');
            $table->timestamp('end_date')->nullable();
            $table->enum('status', ['active', 'canceled', 'paused'])->default('active');           
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
        Schema::dropIfExists('subscriptions');
    }
}
