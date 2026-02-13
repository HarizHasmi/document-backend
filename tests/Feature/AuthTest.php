<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::create([
            'name' => 'Information Technology (IT)',
        ]);
    }

    public function test_user_registration(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'New Employee',
            'email' => 'employee.registered@abc-corp.com',
            'password' => 'password',
            'department_id' => $this->department->id,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.email', 'employee.registered@abc-corp.com');

        $this->assertDatabaseHas('users', [
            'email' => 'employee.registered@abc-corp.com',
            'department_id' => $this->department->id,
        ]);

        $user = User::query()->where('email', 'employee.registered@abc-corp.com')->firstOrFail();
        $this->assertTrue($user->hasRole('employee'));
    }

    public function test_user_login(): void
    {
        User::create([
            'name' => 'Login User',
            'email' => 'employee.login@abc-corp.com',
            'password' => Hash::make('password'),
            'department_id' => $this->department->id,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'employee.login@abc-corp.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'user',
                ],
            ]);
    }

    public function test_user_logout(): void
    {
        $user = User::create([
            'name' => 'Logout User',
            'email' => 'employee.logout@abc-corp.com',
            'password' => Hash::make('password'),
            'department_id' => $this->department->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
