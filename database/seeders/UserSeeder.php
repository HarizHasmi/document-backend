<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('manager', 'web');
        Role::findOrCreate('employee', 'web');

        $departmentIds = Department::query()
            ->pluck('id', 'name');

        $users = [
            [
                'name' => 'Ahmad Faizal',
                'department' => 'Information Technology (IT)',
                'role' => 'admin',
                'factory_state' => 'admin',
            ],
            [
                'name' => 'Nur Aisyah Rahman',
                'department' => 'Human Resources (HR)',
                'role' => 'manager',
                'factory_state' => 'manager',
            ],
            [
                'name' => 'Muhammad Amirul Hakim',
                'department' => 'Finance',
                'role' => 'manager',
                'factory_state' => 'manager',
            ],
            [
                'name' => 'Siti Hajar Zulkifli',
                'department' => 'Information Technology (IT)',
                'role' => 'manager',
                'factory_state' => 'manager',
            ],
            [
                'name' => 'Farhan Iqbal Ismail',
                'department' => 'Marketing',
                'role' => 'manager',
                'factory_state' => 'manager',
            ],
            [
                'name' => 'Nurul Syafiqah Ahmad',
                'department' => 'Human Resources (HR)',
                'role' => 'employee',
                'factory_state' => 'employee',
            ],
            [
                'name' => 'Aiman Danish Razak',
                'department' => 'Finance',
                'role' => 'employee',
                'factory_state' => 'employee',
            ],
            [
                'name' => 'Hafizah Binti Omar',
                'department' => 'Finance',
                'role' => 'employee',
                'factory_state' => 'employee',
            ],
            [
                'name' => 'Irfan Adli Azman',
                'department' => 'Information Technology (IT)',
                'role' => 'employee',
                'factory_state' => 'employee',
            ],
            [
                'name' => 'Puteri Balqis Roslan',
                'department' => 'Marketing',
                'role' => 'employee',
                'factory_state' => 'employee',
            ],
            [
                'name' => 'Zulhilmi Anuar',
                'department' => 'Operations',
                'role' => 'employee',
                'factory_state' => 'employee',
            ],
            [
                'name' => 'Nadia Sofea Iskandar',
                'department' => 'Operations',
                'role' => 'employee',
                'factory_state' => 'employee',
            ],
        ];

        foreach ($users as $entry) {
            $departmentId = $departmentIds[$entry['department']] ?? null;
            if (!$departmentId) {
                continue;
            }

            $email = $this->seedEmailFromName($entry['name']);

            $this->upsertFactoryUser($entry, $email, (int) $departmentId);
        }
    }

    /**
     * @param array{name:string,role:string,factory_state:string} $entry
     */
    private function upsertFactoryUser(array $entry, string $email, int $departmentId): void
    {
        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser) {
            $existingUser->update([
                'name' => $entry['name'],
                'password' => 'password',
                'department_id' => $departmentId,
            ]);
            $existingUser->syncRoles([$entry['role']]);

            return;
        }

        $factoryState = $entry['factory_state'];

        $user = User::factory()
            ->{$factoryState}($departmentId)
            ->create([
                'name' => $entry['name'],
                'email' => $email,
                'password' => 'password',
            ]);

        $user->syncRoles([$entry['role']]);
    }

    private function seedEmailFromName(string $name): string
    {
        $localPart = (string) Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.');

        return "{$localPart}@abc-corp.com";
    }
}
