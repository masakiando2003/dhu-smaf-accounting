<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use App\Model\Cashier;
use App\Model\Item;
use App\Model\Order;
use App\Model\OrderItem;
use Faker\Generator as Faker;

$factory->define(Item::class, function (Faker $faker) {
    $stock = $faker->numberBetween(10, 100);

    return [
        'name'        => 'アイテム'.$faker->unique()->numberBetween(1, 99999),
        'description' => 'テスト用のアイテム',
        'price'       => $faker->numberBetween(1, 10) * 50,
        'size'        => 'M',
        'weight'      => $faker->numberBetween(10, 500),
        'weight_unit' => 'g',
        'init_stock'  => $stock,
        'stock'       => $stock,
        'remarks'     => null,
    ];
});

$factory->define(Order::class, function (Faker $faker) {
    return [
        'num_of_people' => $faker->numberBetween(1, 5),
        'paid'          => 1000,
        'change'        => 0,
        'remarks'       => null,
    ];
});

$factory->define(OrderItem::class, function (Faker $faker) {
    return [
        'order_id' => function () {
            return factory(Order::class)->create()->id;
        },
        'item_id'  => function () {
            return factory(Item::class)->create()->id;
        },
        'price'    => 300,
        'quantity' => 1,
    ];
});

$factory->define(Cashier::class, function (Faker $faker) {
    return [
        'transaction_time' => now(),
        'cashier_type'     => 'payment',
        'description'      => 'テスト用の支払',
        'income_amount'    => 0,
        'payment_amount'   => 400,
        'deduction_amount' => -400,
        'remarks'          => null,
    ];
});
