<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    private const DEFAULT_NAMES = [
        'Ahmad Faiz',
        'Nurul Aina',
        'Siti Aisyah',
        'Muhammad Danish',
        'Farah Nabila',
        'Haziq Imran',
        'Nadia Sofia',
        'Izzat Hakim',
    ];

    protected static int $sequence = 1;

    protected static int $adminSequence = 1;

    protected static int $managerSequence = 1;

    protected static int $employeeSequence = 1;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = self::$sequence++;
        $name = self::DEFAULT_NAMES[($sequence - 1) % count(self::DEFAULT_NAMES)];

        return [
            'name' => $name,
            'email' => $this->seedEmailFromName($name, 'example.test', $sequence),
            'email_verified_at' => now(),
            'password' => 'password',
            'remember_token' => sprintf('tok%07d', $sequence),
            'department_id' => $this->resolveDepartmentId(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inDepartment(int $departmentId): static
    {
        return $this->state(fn () => [
            'department_id' => $departmentId,
        ]);
    }

    public function named(string $name, string $domain = 'example.test'): static
    {
        return $this->state(function () use ($name, $domain): array {
            $sequence = self::$sequence++;

            return [
                'name' => $name,
                'email' => $this->seedEmailFromName($name, $domain, $sequence),
            ];
        });
    }

    public function admin(?int $departmentId = null): static
    {
        $sequence = self::$adminSequence++;
        $name = "Admin User {$sequence}";

        return $this->state(fn () => [
            'name' => $name,
            'email' => $this->seedEmailFromName($name, 'example.test', $sequence),
            'department_id' => $this->resolveDepartmentId($departmentId),
        ])->afterCreating(function (User $user): void {
            Role::findOrCreate('admin', 'web');
            $user->syncRoles(['admin']);
        });
    }

    public function manager(?int $departmentId = null): static
    {
        $sequence = self::$managerSequence++;
        $name = "Manager User {$sequence}";

        return $this->state(fn () => [
            'name' => $name,
            'email' => $this->seedEmailFromName($name, 'example.test', $sequence),
            'department_id' => $this->resolveDepartmentId($departmentId),
        ])->afterCreating(function (User $user): void {
            Role::findOrCreate('manager', 'web');
            $user->syncRoles(['manager']);
        });
    }

    public function employee(?int $departmentId = null): static
    {
        $sequence = self::$employeeSequence++;
        $name = "Employee User {$sequence}";

        return $this->state(fn () => [
            'name' => $name,
            'email' => $this->seedEmailFromName($name, 'example.test', $sequence),
            'department_id' => $this->resolveDepartmentId($departmentId),
        ])->afterCreating(function (User $user): void {
            Role::findOrCreate('employee', 'web');
            $user->syncRoles(['employee']);
        });
    }

    private function resolveDepartmentId(?int $preferredDepartmentId = null): int
    {
        if ($preferredDepartmentId) {
            return $preferredDepartmentId;
        }

        $existingDepartmentId = Department::query()->value('id');
        if ($existingDepartmentId) {
            return (int) $existingDepartmentId;
        }

        return Department::query()
            ->firstOrCreate(['name' => 'Human Resources (HR)'])
            ->id;
    }

    private function seedEmailFromName(string $name, string $domain, int $sequence): string
    {
        $localPart = (string) Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.');

        $suffix = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);

        return "{$localPart}.{$suffix}@{$domain}";
    }
}
