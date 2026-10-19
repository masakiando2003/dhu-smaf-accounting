<?php

namespace Tests\Feature;

use App\Model\Cashier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 出納記録フォームの入力値を作成する
     *
     * @param  array  $overrides  上書きする項目
     * @return array
     */
    private function cashierInput(array $overrides = [])
    {
        return array_merge([
            'transactionTimeType' => 'auto',
            'type'                => 'payment',
            'description'         => '材料費',
            'income_amount'       => '0',
            'payment_amount'      => '400',
            'deduction_amount'    => '-400',
            'remarks'             => '備考',
        ], $overrides);
    }

    public function testIndexListsCashierRecords()
    {
        factory(Cashier::class)->create(['description' => '材料費']);

        $this->get('/admin/cashier')
             ->assertOk()
             ->assertSee('現金出納帳')
             ->assertSee('材料費');
    }

    public function testCreatePageIsShown()
    {
        $this->get('/admin/cashier/create')
             ->assertOk()
             ->assertSee('新規出納記録作成');
    }

    public function testDetailPageShowsRecord()
    {
        $cashier = factory(Cashier::class)->create([
            'transaction_time' => '2019-11-30 10:15:00',
            'description'      => '材料費',
        ]);

        $this->get('/admin/cashier/detail/'.$cashier->id)
             ->assertOk()
             ->assertSee('出納記録編集')
             ->assertSee('材料費')
             ->assertSee('2019/11/30');
    }

    public function testRegisterCreatesPaymentRecord()
    {
        $this->post('/admin/cashier/register', $this->cashierInput())
             ->assertRedirect('/admin/cashier')
             ->assertSessionHas('success_msg');

        $this->assertDatabaseHas('cashier', [
            'cashier_type'     => 'payment',
            'description'      => '材料費',
            'income_amount'    => 0,
            'payment_amount'   => 400,
            'deduction_amount' => -400,
            'remarks'          => '備考',
        ]);
    }

    public function testRegisterWithManualTimeUsesInputTime()
    {
        $this->post('/admin/cashier/register', $this->cashierInput([
            'transactionTimeType' => 'manual',
            'transactionDate'     => '2019/11/30',
            'transactionTime'     => '10:15:00',
        ]));

        $this->assertSame('2019-11-30 10:15:00', Cashier::first()->transaction_time);
    }

    public function testRegisterRejectsNonNumericAmount()
    {
        $this->from('/admin/cashier/create')
             ->post('/admin/cashier/register', $this->cashierInput(['payment_amount' => '四百']))
             ->assertRedirect('/admin/cashier/create')
             ->assertSessionHasErrors(['payment_amount' => '支払金額に数字以外が試用されています']);

        $this->assertSame(0, Cashier::count());
    }

    public function testRegisterRejectsMissingAmount()
    {
        $this->from('/admin/cashier/create')
             ->post('/admin/cashier/register', $this->cashierInput(['income_amount' => '']))
             ->assertSessionHasErrors('income_amount');

        $this->assertSame(0, Cashier::count());
    }

    public function testEditUpdatesRecord()
    {
        $cashier = factory(Cashier::class)->create(['transaction_time' => '2019-11-30 10:15:00']);

        $this->post('/admin/cashier/edit/'.$cashier->id, $this->cashierInput([
                'cashier_id'       => (string) $cashier->id,
                'description'      => '包装資材',
                'payment_amount'   => '500',
                'deduction_amount' => '-500',
             ]))
             ->assertRedirect('/admin/cashier')
             ->assertSessionHas('success_msg', '出納記録(ID: '.$cashier->id.')を更新しました');

        $this->assertDatabaseHas('cashier', [
            'id'               => $cashier->id,
            'description'      => '包装資材',
            'payment_amount'   => 500,
            'deduction_amount' => -500,
            // transactionTimeType が auto のときは取引日時を変えない
            'transaction_time' => '2019-11-30 10:15:00',
        ]);
    }

    public function testEditRejectsNonNumericAmount()
    {
        $cashier = factory(Cashier::class)->create(['income_amount' => 0]);

        $this->from('/admin/cashier/detail/'.$cashier->id)
             ->post('/admin/cashier/edit/'.$cashier->id, $this->cashierInput([
                'cashier_id'    => (string) $cashier->id,
                'income_amount' => '千',
             ]))
             ->assertRedirect('/admin/cashier/detail/'.$cashier->id)
             ->assertSessionHasErrors(['income_amount' => '収入金額に数字以外が試用されています']);

        $this->assertSame(0, (int) $cashier->fresh()->income_amount);
    }

    public function testEditWithManualTimeUpdatesTransactionTime()
    {
        $cashier = factory(Cashier::class)->create(['transaction_time' => '2019-11-30 10:15:00']);

        $this->post('/admin/cashier/edit/'.$cashier->id, $this->cashierInput([
            'cashier_id'          => (string) $cashier->id,
            'transactionTimeType' => 'manual',
            'transactionDate'     => '2019/12/01',
            'transactionTime'     => '14:30:00',
        ]));

        $this->assertSame('2019-12-01 14:30:00', $cashier->fresh()->transaction_time);
    }

    public function testDeleteSoftDeletesRecord()
    {
        $cashier = factory(Cashier::class)->create();

        $this->delete('/admin/cashier/delete/', ['del_id' => $cashier->id])
             ->assertRedirect('/admin/cashier')
             ->assertSessionHas('success_msg', '出納記録(ID: '.$cashier->id.')を削除しました');

        $this->assertSoftDeleted('cashier', ['id' => $cashier->id]);
    }
}
