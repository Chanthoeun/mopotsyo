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
            'name' => 'employee',
            'guard_name' => 'web',
        ]);
        Role::create([
            'name' => 'supervisor',
            'guard_name' => 'web',
        ]);
        Role::create([
            'name' => 'head_of_department',
            'guard_name' => 'web',
        ]);
        Role::create([
            'name' => 'acting_director',
            'guard_name' => 'web',
        ]);
    }
}
