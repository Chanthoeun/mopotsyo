<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        Role::create([
            'name' => 'staff',
            'guard_name' => 'web',
        ]);
        Role::create([
            'name' => 'patient',
            'guard_name' => 'web',
        ]);
        Role::create([
            'name' => 'doctor',
            'guard_name' => 'web',
        ]);
        Role::create([
            'name' => 'partner',
            'guard_name' => 'web',
        ]);
    }
}
