<?php

use Illuminate\Database\Seeder;

class CompanyInfoTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('company_info')->insert([
            [
                'company_name'      => '株式会社SmaF',
                'description'       => '綿菓子、ポテト、ポップコーン販売',
                'setup_date'        => '2019-11-02',
                'initial_captial'   => 32000,
                'cash'              => 32000
            ],
        ]);
    }
}
