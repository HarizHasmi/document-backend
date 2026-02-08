<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::create(['name'=>'admin']);
        $manager = Role::create(['name'=>'manager']);
        $employee = Role::create(['name'=>'employee']);

        User::factory()->create([
            'email'=>'admin@test.com'
        ])->assignRole($admin);

        User::factory(4)->create()->each(fn($u)=>$u->assignRole($manager));
        User::factory(7)->create()->each(fn($u)=>$u->assignRole($employee));
    }
}
