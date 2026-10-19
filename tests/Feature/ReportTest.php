<?php

namespace Tests\Feature;

use CompanyInfoTableSeeder;
use CompanyMembersTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TestDataSeeder;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CompanyInfoTableSeeder::class);
        $this->seed(CompanyMembersTableSeeder::class);
    }

    public function testReportIsShownWithoutOrders()
    {
        $this->get('/admin/report')
             ->assertOk()
             ->assertSee('貸借対照表')
             ->assertSee('株式会社SmaF');
    }

    public function testReportIsShownWithTestData()
    {
        $this->seed(TestDataSeeder::class);

        $this->get('/admin/report')
             ->assertOk()
             ->assertSee('綿菓子')
             ->assertSee('ポテト')
             ->assertSee('材料費');
    }
}
