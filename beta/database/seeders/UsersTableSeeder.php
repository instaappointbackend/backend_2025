<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        // Create a vendor
        $vendor = User::create([
            'name'            => 'Admin',
            'email'           => 'admin@gmail.com',
            'password'        => Hash::make('123'),
            'mobile'          => '1234567890',
            'gender'          => 'male',
            'dob'             => $faker->date(),
            'role'            => 'admin',
            'status'          => true,
            'is_kyc_completed'=> true,
            'reference_code'  => '',
            'vendor_id'       => null, // Vendor's own record, so no vendor_id.
        ]);
        // Create a vendor
        $vendor = User::create([
            'name'            => 'Vendor One',
            'email'           => 'vendor1@example.com',
            'password'        => Hash::make('password'),
            'mobile'          => '1234567563',
            'gender'          => 'other',
            'dob'             => $faker->date(),
            'role'            => 'vendor',
            'status'          => true,
            'is_kyc_completed'=> false,
            'reference_code'  => 'VENDOR1',
            'vendor_id'       => null, // Vendor's own record, so no vendor_id.
        ]);

        // Create team members for the vendor
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'name'            => "Team Member $i",
                'email'           => "teammember{$i}@example.com",
                'password'        => Hash::make('password'),
                'mobile'          => $faker->numerify('##########'),
                'gender'          => $faker->randomElement(['male', 'female', 'other']),
                'dob'             => $faker->date(),
                'role'            => 'team_member',
                'status'          => true,
                'is_kyc_completed'=> false,
                'reference_code'  => strtoupper($faker->lexify('?????')),
                'vendor_id'       => $vendor->id, // Associate with vendor.
            ]);
        }

        // Create individual customer users
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name'            => $faker->name,
                'email'           => $faker->unique()->safeEmail,
                'password'        => Hash::make('password'),
                'mobile'          => $faker->numerify('##########'),
                'gender'          => $faker->randomElement(['male', 'female', 'other']),
                'dob'             => $faker->date(),
                'role'            => 'customer',
                'status'          => true,
                'is_kyc_completed'=> false,
                'reference_code'  => strtoupper($faker->lexify('?????')),
                'vendor_id'       => null, // Customer, not associated with any vendor.
            ]);
        }
    }
}
