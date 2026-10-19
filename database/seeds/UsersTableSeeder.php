<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'name'           => 'smaf',
                'email'          => 'smaf@dhw.ac.jp',
                'password'       => Hash::make('password')
            ],
        ]);
    }
}
