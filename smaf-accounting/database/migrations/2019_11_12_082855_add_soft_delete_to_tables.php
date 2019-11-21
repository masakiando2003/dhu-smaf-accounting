<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeleteToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('item', 'items');
        Schema::rename('order', 'orders');
        Schema::rename('order_item', 'order_items');

        Schema::table('items', function(Blueprint $table)
		{
			$table->softDeletes();
		});

        Schema::table('orders', function(Blueprint $table)
		{
			$table->softDeletes();
        });
        
        Schema::table('order_items', function(Blueprint $table)
		{
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
        Schema::rename('items', 'item');
        Schema::rename('orders', 'order');
        Schema::rename('order_items', 'order_item');

        Schema::table('item', function(Blueprint $table)
		{
            $table->dropSoftDeletes();
        });

        Schema::table('order', function(Blueprint $table)
		{
            $table->dropSoftDeletes();
		});

        Schema::table('order_item', function(Blueprint $table)
		{
            $table->dropSoftDeletes();
		});
    }
}
