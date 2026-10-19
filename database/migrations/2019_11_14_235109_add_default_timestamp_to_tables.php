<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultTimestampToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE items MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE items MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        
        DB::statement('ALTER TABLE orders MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE orders MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        
        DB::statement('ALTER TABLE order_items MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE order_items MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        
        DB::statement('ALTER TABLE cashier MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE cashier MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE items MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT NULL');
        DB::statement('ALTER TABLE items MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT NULL');
        
        DB::statement('ALTER TABLE orders MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT NULL');
        DB::statement('ALTER TABLE orders MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT NULL');
        
        DB::statement('ALTER TABLE order_items MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT NULL');
        DB::statement('ALTER TABLE order_items MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT NULL');
        
        DB::statement('ALTER TABLE cashier MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT NULL');
        DB::statement('ALTER TABLE cashier MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT NULL');
    }
}
