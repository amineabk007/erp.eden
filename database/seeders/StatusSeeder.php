<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('statuses')->insert([
            ['name' => 'draft', 'created_at'=>now(), 'updated_at'=>now()],
            ['name' => 'validated', 'created_at'=>now(), 'updated_at'=>now()],
            ['name' => 'paid', 'created_at'=>now(), 'updated_at'=>now()],
            ['name' => 'cancelled', 'created_at'=>now(), 'updated_at'=>now()],
        ]);
    }
}
