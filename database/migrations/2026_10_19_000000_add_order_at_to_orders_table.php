<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 注文日時（order_at）を追加する
 *
 * これまでは created_at を注文日時として使っていたため、既存の注文は created_at の値を引き継ぐ.
 * 以降、created_at はレコードを作成した日時、order_at は注文日時として使い分ける.
 */
class AddOrderAtToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dateTime('order_at')->nullable()->after('remarks');
        });

        DB::statement('UPDATE orders SET order_at = COALESCE(created_at, CURRENT_TIMESTAMP)');

        DB::statement('ALTER TABLE orders MODIFY COLUMN order_at DATETIME NOT NULL');
        Schema::table('orders', function (Blueprint $table) {
            $table->index('order_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['order_at']);
            $table->dropColumn('order_at');
        });
    }
}
