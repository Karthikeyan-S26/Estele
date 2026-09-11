<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Demo buy-back vendors — accounts that can be reached via the tokenized
     * sell.vendor.invitation emails. See SellRequestSeeder for the invitation
     * emails themselves.
     */
    public function run(): void
    {
        $vendors = [
            ['name' => 'Anand Jewellers', 'email' => 'vendor1@estele.in', 'phone' => '9876500001'],
            ['name' => 'Pari Jewellers', 'email' => 'vendor2@estele.in', 'phone' => '9876500002'],
            ['name' => 'Sheetal Gold House', 'email' => 'vendor3@estele.in', 'phone' => '9876500003'],
        ];

        foreach ($vendors as $vendor) {
            $user = User::firstOrCreate(
                ['email' => $vendor['email']],
                [
                    'name' => $vendor['name'],
                    'phone' => $vendor['phone'],
                    'password' => 'password123',
                ]
            );
            $user->assignRole('vendor');
        }
    }
}