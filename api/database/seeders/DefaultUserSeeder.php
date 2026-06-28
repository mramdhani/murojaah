<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'default@murojaah.app'],
            [
                'name' => 'Penghafal',
                'password' => bcrypt('murojaah'),
            ]
        );
    }
}
