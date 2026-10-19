<?php

namespace Tests\Unit\Services;

use App\Model\Cashier;
use App\Services\CashierService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SimulatesDatabaseError;
use Tests\TestCase;

class CashierServiceTest extends TestCase
{
    use RefreshDatabase;
    use SimulatesDatabaseError;

    /** @var \App\Services\CashierService */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(CashierService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * 出納記録の入力を作成する
     *
     * @param  array  $overrides  上書きする項目
     * @return array
     */
    private function cashierData(array $overrides = [])
    {
        return array_merge([
            'cashier_type'     => 'payment',
            'description'      => '材料費',
            'income_amount'    => 0,
            'payment_amount'   => 400,
            'deduction_amount' => -400,
            'remarks'          => '備考',
        ], $overrides);
    }

    public function testPaginateReturnsRecordsAndTotal()
    {
        factory(Cashier::class, 3)->create();

        $result = $this->service->paginate(2);

        $this->assertSame(3, $result['total_count']);
        $this->assertCount(2, $result['records']);
    }

    public function testCreateUsesCurrentTimeWhenTimeIsNotGiven()
    {
        Carbon::setTestNow('2019-11-30 12:34:56');

        $cashier = $this->service->create($this->cashierData());

        $this->assertDatabaseHas('cashier', [
            'id'               => $cashier->id,
            'transaction_time' => '2019-11-30 12:34:56',
        ] + $this->cashierData());
    }

    public function testCreateUsesGivenTime()
    {
        $cashier = $this->service->create($this->cashierData(), '2019-12-01 09:00:00');

        $this->assertSame('2019-12-01 09:00:00', $cashier->fresh()->transaction_time);
    }

    public function testUpdateKeepsTimeAndTypeWhenTimeIsNotGiven()
    {
        $cashier = $this->service->create($this->cashierData(), '2019-11-30 10:00:00');

        $this->service->update($cashier->id, $this->cashierData([
            'cashier_type'     => 'income',
            'description'      => '包装資材',
            'payment_amount'   => 500,
            'deduction_amount' => -500,
        ]));

        $this->assertDatabaseHas('cashier', [
            'id'               => $cashier->id,
            'cashier_type'     => 'payment',
            'description'      => '包装資材',
            'payment_amount'   => 500,
            'deduction_amount' => -500,
            'transaction_time' => '2019-11-30 10:00:00',
        ]);
    }

    public function testUpdateChangesTimeWhenGiven()
    {
        $cashier = $this->service->create($this->cashierData(), '2019-11-30 10:00:00');

        $this->service->update($cashier->id, $this->cashierData(), '2019-12-01 15:00:00');

        $this->assertSame('2019-12-01 15:00:00', $cashier->fresh()->transaction_time);
    }

    public function testFindThrowsWhenRecordIsMissing()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->service->find(999999);
    }

    public function testDeleteRollsBackAndRethrowsOriginalException()
    {
        $cashier = factory(Cashier::class)->create();
        // 論理削除の後の保存で DB エラーが起きたことにする
        $this->failOnSaving(Cashier::class);

        $this->assertThrowsSimulatedDatabaseError(function () use ($cashier) {
            $this->service->delete($cashier->id);
        });

        $this->assertDatabaseHas('cashier', ['id' => $cashier->id, 'deleted_at' => null]);
    }

    public function testDeleteSoftDeletesRecord()
    {
        $cashier = factory(Cashier::class)->create();

        $this->service->delete($cashier->id);

        $this->assertSoftDeleted('cashier', ['id' => $cashier->id]);
    }
}
