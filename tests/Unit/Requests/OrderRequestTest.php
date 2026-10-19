<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\OrderRequest;
use Tests\TestCase;

class OrderRequestTest extends TestCase
{
    public function testOrderItemsReturnsEachRowWithItem()
    {
        $request = OrderRequest::create('/admin/orders/register', 'POST', [
            'order_item_count' => '2',
            'item_id_1'        => '10',
            'price_1'          => '300',
            'quantity_1'       => '2',
            'item_id_2'        => '20',
            'price_2'          => '150',
            'quantity_2'       => '1',
        ]);

        $this->assertSame([
            ['item_id' => '10', 'price' => '300', 'quantity' => '2', 'delete' => false],
            ['item_id' => '20', 'price' => '150', 'quantity' => '1', 'delete' => false],
        ], $request->orderItems());
    }

    public function testOrderItemsSkipsRowsWithoutItem()
    {
        $request = OrderRequest::create('/admin/orders/register', 'POST', [
            'order_item_count' => '3',
            'item_id_1'        => '10',
            'price_1'          => '300',
            'quantity_1'       => '1',
            'item_id_2'        => '',
            'price_2'          => '150',
            'quantity_2'       => '1',
            // 3行目は item_id_3 自体がない
        ]);

        $order_items = $request->orderItems();

        $this->assertCount(1, $order_items);
        $this->assertSame('10', $order_items[0]['item_id']);
    }

    public function testOrderItemsMarksCheckedDeleteFlag()
    {
        $request = OrderRequest::create('/admin/orders/edit', 'POST', [
            'order_item_count' => '2',
            'item_id_1'        => '10',
            'delete_item_1'    => 'on',
            'item_id_2'        => '20',
        ]);

        $order_items = $request->orderItems();

        $this->assertTrue($order_items[0]['delete']);
        $this->assertFalse($order_items[1]['delete']);
        // 入力されていない単価・数量は null になる
        $this->assertNull($order_items[1]['price']);
        $this->assertNull($order_items[1]['quantity']);
    }

    public function testOrderItemsIsEmptyWithoutCount()
    {
        $request = OrderRequest::create('/admin/orders/register', 'POST', [
            'item_id_1' => '10',
        ]);

        $this->assertSame([], $request->orderItems());
    }

    public function testManualTransactionTimeIsFormatted()
    {
        $request = OrderRequest::create('/admin/orders/register', 'POST', [
            'transactionTimeType' => 'manual',
            'transactionDate'     => '2019/11/30',
            'transactionTime'     => '10:15:00',
        ]);

        $this->assertSame('2019-11-30 10:15:00', $request->manualTransactionTime());
    }

    public function testManualTransactionTimeIsNullWhenAuto()
    {
        $request = OrderRequest::create('/admin/orders/register', 'POST', [
            'transactionTimeType' => 'auto',
            'transactionDate'     => '2019/11/30',
            'transactionTime'     => '10:15:00',
        ]);

        $this->assertNull($request->manualTransactionTime());
    }

    public function testRulesAndMessagesMatch()
    {
        $request = new OrderRequest();

        $this->assertTrue($request->authorize());
        foreach ($request->rules() as $field => $rules) {
            foreach (explode('|', $rules) as $rule) {
                $rule_name = explode(':', $rule)[0];
                $this->assertArrayHasKey("{$field}.{$rule_name}", $request->messages());
            }
        }
    }
}
