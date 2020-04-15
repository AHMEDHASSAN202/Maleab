<?php

use Illuminate\Database\Seeder;

class RegisterTokensSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Illuminate\Support\Facades\DB::table('register_tokens')->insert(['token' => bin2hex(openssl_random_pseudo_bytes(16))]);
    }
}
