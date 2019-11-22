<?php

use Illuminate\Database\Seeder;

use Carbon\Carbon;

class CompanyMembersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('company_members')->insert([
            [
                'name'              => 'Lonely',
                'email'             => '',
                'role'              => 1,
                'position'          => '社長',
                'capital'           => 8000,
                'num_of_share'      => 8,
                'share_percentage'  => 25,
                'status'            => 1,
                'created_at'        => Carbon::now()      
            ],
        ]);
        DB::table('company_members')->insert([
            [
                'name'              => '齊藤浩輝',
                'email'             => '',
                'role'              => 2,
                'position'          => '企画',
                'capital'           => 8000,
                'num_of_share'      => 8,
                'share_percentage'  => 25,
                'status'            => 1,
                'created_at'        => Carbon::now()      
            ],
        ]);
        DB::table('company_members')->insert([
            [
                'name'              => '村井将樹',
                'email'             => '',
                'role'              => 2,
                'position'          => '発注',
                'capital'           => 8000,
                'num_of_share'      => 8,
                'share_percentage'  => 25,
                'status'            => 1,
                'created_at'        => Carbon::now()      
            ],
        ]);
        DB::table('company_members')->insert([
            [
                'name'              => '楊家祺',
                'email'             => 'a18dc593@dhw.ac.jp',
                'role'              => 2,
                'position'          => '会計',
                'capital'           => 8000,
                'num_of_share'      => 8,
                'share_percentage'  => 25,
                'status'            => 1,
                'created_at'        => Carbon::now()      
            ],
        ]);
    }
}
