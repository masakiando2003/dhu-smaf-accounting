<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashierTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cashier', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('transaction_time');
            $table->string('cashier_type');
            $table->string('description');
            $table->integer('income_amount');
            $table->integer('payment_amount');
            $table->integer('deduction_amount');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cashier');
    }
}
