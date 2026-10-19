<?php

namespace Tests\Unit\Models;

use App\Model\CompanyMembers;
use Tests\TestCase;

class CompanyMembersTest extends TestCase
{
    public function testFillableColumnsMatchTable()
    {
        $member = (new CompanyMembers())->fill([
            'name'             => '山田',
            'capital'          => 10000,
            'num_of_share'     => 10,
            'share_percentage' => 50,
        ]);

        $this->assertSame(10000, $member->capital);
        $this->assertSame(10, $member->num_of_share);
    }
}
