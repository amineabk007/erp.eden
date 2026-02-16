<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure at least one company exists
        $company = Company::first() ?? Company::create([
            'name' => 'Default Company',
        ]);

        User::updateOrCreate(
            ['email' => 'admin@erp.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin1234'),
                'role' => 'admin',
                'company_id' => $company->id,
            ]
        );
    }
}
