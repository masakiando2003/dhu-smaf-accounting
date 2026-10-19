<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 動作確認用のテストデータ（商品・注文・現金出納帳）
 *
 * DatabaseSeeder には含めていないため、必要なときだけ次のコマンドで投入する.
 *   php artisan db:seed --class=TestDataSeeder
 *
 * 会社情報（company_info）とメンバー情報（company_members）は変更しない.
 */
class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $item_names = ['綿菓子', 'ポテト', 'ポップコーン'];
        if (DB::table('items')->whereIn('name', $item_names)->exists()) {
            $this->command->warn('テストデータの商品がすでに存在するため、スキップしました');
            return;
        }

        DB::transaction(function () {
            $now = Carbon::now();

            // 綿菓子は「在庫が少ない商品」の確認用に、在庫を初期在庫の2割以下にしている
            $cotton_candy_id = DB::table('items')->insertGetId([
                'name'        => '綿菓子',
                'description' => 'ふわふわの綿菓子',
                'price'       => 300,
                'size'        => 'M',
                'weight'      => 30,
                'weight_unit' => 'g',
                'init_stock'  => 50,
                'stock'       => 5,
                'remarks'     => 'テストデータ',
            ]);
            $potato_id = DB::table('items')->insertGetId([
                'name'        => 'ポテト',
                'description' => '揚げたてのフライドポテト',
                'price'       => 150,
                'size'        => 'M',
                'weight'      => 100,
                'weight_unit' => 'g',
                'init_stock'  => 100,
                'stock'       => 90,
                'remarks'     => 'テストデータ',
            ]);
            DB::table('items')->insert([
                'name'        => 'ポップコーン',
                'description' => '塩味のポップコーン',
                'price'       => 200,
                'size'        => 'M',
                'weight'      => 50,
                'weight_unit' => 'g',
                'init_stock'  => 80,
                'stock'       => 80,
                'remarks'     => 'テストデータ',
            ]);

            // 注文日時はレポートの集計日（学園祭の 2019-11-30・2019-12-01）に合わせる.
            // 2件目は「時間帯の終わり（13:59）の秒も集計に含まれる」ことの確認用.
            // 3件目は「削除済みの注文が集計に含まれない」ことの確認用.
            $orders = [
                ['num_of_people' => 2, 'paid' => 1000, 'deleted' => false, 'order_at' => '2019-11-30 10:15:00',
                 'items' => [[$cotton_candy_id, 300, 2], [$potato_id, 150, 1]]],
                ['num_of_people' => 1, 'paid' => 500, 'deleted' => false, 'order_at' => '2019-12-01 13:59:30',
                 'items' => [[$cotton_candy_id, 300, 1], [$potato_id, 150, 1]]],
                ['num_of_people' => 3, 'paid' => 300, 'deleted' => true, 'order_at' => '2019-11-30 11:00:00',
                 'items' => [[$cotton_candy_id, 300, 1]]],
            ];

            foreach ($orders as $order) {
                $order_total = 0;
                foreach ($order['items'] as [$item_id, $price, $quantity]) {
                    $order_total += $price * $quantity;
                }
                $change = $order['paid'] - $order_total;
                $deleted_at = $order['deleted'] ? $now : null;

                $order_id = DB::table('orders')->insertGetId([
                    'num_of_people' => $order['num_of_people'],
                    'paid'          => $order['paid'],
                    'change'        => $change,
                    'remarks'       => 'テストデータ',
                    'order_at'      => $order['order_at'],
                    'created_at'    => $now,
                    'deleted_at'    => $deleted_at,
                ]);

                foreach ($order['items'] as [$item_id, $price, $quantity]) {
                    DB::table('order_items')->insert([
                        'order_id'   => $order_id,
                        'item_id'    => $item_id,
                        'price'      => $price,
                        'quantity'   => $quantity,
                        'created_at' => $now,
                        'deleted_at' => $deleted_at,
                    ]);
                }

                // 注文登録時にアプリが作る「収入」の出納記録と同じ内容
                DB::table('cashier')->insert([
                    'transaction_time' => $order['order_at'],
                    'cashier_type'     => 'income',
                    'order_id'         => $order_id,
                    'description'      => '注文ID: '.$order_id.'に支払する',
                    'income_amount'    => $order['paid'],
                    'payment_amount'   => $change,
                    'deduction_amount' => $order_total,
                    'remarks'          => 'テストデータ',
                    'created_at'       => $now,
                    'deleted_at'       => $deleted_at,
                ]);
            }

            // 材料費は学園祭の前日に支払った想定
            DB::table('cashier')->insert([
                'transaction_time' => '2019-11-29 18:00:00',
                'cashier_type'     => 'payment',
                'description'      => '材料費',
                'income_amount'    => 0,
                'payment_amount'   => 400,
                'deduction_amount' => -400,
                'remarks'          => 'テストデータ',
                'created_at'       => $now,
            ]);
        });
    }
}
