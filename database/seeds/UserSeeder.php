<?php

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
//        factory(\App\User::class, 10)->create();
        \App\User::create([
            'role'      => 'admin',
            'name'      => 'Admin',
            'email'     => 'admin@gmail.com',
            'password'  => \Illuminate\Support\Facades\Hash::make('password'),
            'phone'     => '01096206374',
            'address'   => 'القاهرة'
        ]);
        \App\User::create([
            'role'      => 'playground',
            'name'      => 'Playground',
            'email'     => 'playground@gmail.com',
            'password'  => \Illuminate\Support\Facades\Hash::make('password'),
            'phone'     => '01096206378',
            'address'   => 'القاهرة',
        ]);
        \App\User::create([
            'role'      => 'user',
            'name'      => 'User',
            'email'     => 'user@gmail.com',
            'password'  => \Illuminate\Support\Facades\Hash::make('password'),
            'phone'     => '01096206377',
            'address'   => 'القاهرة'
        ]);
    }
}
