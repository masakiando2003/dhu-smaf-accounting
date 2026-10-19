<?php

namespace Tests\Unit\Database;

use App\Model\Item;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function testDatabaseSessionUsesAppTimezone()
    {
        $time_zone = DB::selectOne('SELECT @@session.time_zone AS time_zone')->time_zone;

        $this->assertSame('+09:00', $time_zone);
        $this->assertSame('Asia/Tokyo', config('app.timezone'));
    }

    public function testDatabaseNowMatchesAppNow()
    {
        $db_now = Carbon::parse(DB::selectOne('SELECT NOW() AS now')->now);

        $this->assertLessThan(60, abs($db_now->diffInSeconds(Carbon::now(), false)));
    }

    public function testDefaultCreatedAtIsInAppTimezone()
    {
        // created_at を指定しないと、DB の初期値（CURRENT_TIMESTAMP）が入る
        $item = factory(Item::class)->create();

        $this->assertLessThan(60, abs($item->fresh()->created_at->diffInSeconds(Carbon::now(), false)));
    }
}
