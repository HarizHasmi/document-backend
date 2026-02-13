<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private Department $hrDepartment;
    private Department $itDepartment;
    private Category $policyCategory;
    private Category $reportCategory;
    private User $admin;
    private User $hrManager;
    private User $itManager;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->hrDepartment = Department::create(['name' => 'Human Resources (HR)']);
        $this->itDepartment = Department::create(['name' => 'Information Technology (IT)']);

        $this->policyCategory = Category::create([
            'title' => 'Policy',
            'description' => 'Policy documents',
        ]);
        $this->reportCategory = Category::create([
            'title' => 'Report',
            'description' => 'Report documents',
        ]);

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('manager', 'web');
        Role::findOrCreate('employee', 'web');

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.local',
            'password' => Hash::make('password'),
            'department_id' => $this->itDepartment->id,
        ]);
        $this->admin->assignRole('admin');

        $this->hrManager = User::create([
            'name' => 'HR Manager',
            'email' => 'manager.hr@test.local',
            'password' => Hash::make('password'),
            'department_id' => $this->hrDepartment->id,
        ]);
        $this->hrManager->assignRole('manager');

        $this->itManager = User::create([
            'name' => 'IT Manager',
            'email' => 'manager.it@test.local',
            'password' => Hash::make('password'),
            'department_id' => $this->itDepartment->id,
        ]);
        $this->itManager->assignRole('manager');

        $this->employee = User::create([
            'name' => 'IT Employee',
            'email' => 'employee.it@test.local',
            'password' => Hash::make('password'),
            'department_id' => $this->itDepartment->id,
        ]);
        $this->employee->assignRole('employee');
    }

    public function test_manager_can_upload_document_to_own_department(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->hrManager);

        $response = $this->postJson('/api/v1/documents', [
            'title' => 'HR Compliance Policy',
            'description' => 'Internal HR compliance policy',
            'file' => UploadedFile::fake()->create('hr-policy.pdf', 200, 'application/pdf'),
            'category_id' => $this->policyCategory->id,
            'department_id' => $this->hrDepartment->id,
            'access_level' => 'department',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.title', 'HR Compliance Policy');

        $document = Document::query()->where('title', 'HR Compliance Policy')->firstOrFail();

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'uploaded_by' => $this->hrManager->id,
            'department_id' => $this->hrDepartment->id,
        ]);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_manager_cannot_upload_document_to_other_department(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->hrManager);

        $this->postJson('/api/v1/documents', [
            'title' => 'Cross Department Upload Attempt',
            'description' => 'This should be blocked',
            'file' => UploadedFile::fake()->create('blocked.pdf', 100, 'application/pdf'),
            'category_id' => $this->policyCategory->id,
            'department_id' => $this->itDepartment->id,
            'access_level' => 'public',
        ])->assertStatus(403);
    }

    public function test_employee_cannot_upload_document(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->employee);

        $this->postJson('/api/v1/documents', [
            'title' => 'Unauthorized Upload',
            'description' => 'Employees cannot upload',
            'file' => UploadedFile::fake()->create('nope.pdf', 100, 'application/pdf'),
            'category_id' => $this->policyCategory->id,
            'department_id' => $this->itDepartment->id,
            'access_level' => 'public',
        ])->assertStatus(403);
    }

    public function test_user_can_search_documents(): void
    {
        Sanctum::actingAs($this->employee);

        $this->createDocument([
            'title' => 'Information Security Handbook',
            'description' => 'Security controls and standards',
            'access_level' => 'public',
        ]);

        $this->createDocument([
            'title' => 'Release Checklist',
            'description' => 'Security hardening checklist for production rollout',
            'access_level' => 'public',
        ]);

        $this->createDocument([
            'title' => 'Marketing Launch Plan',
            'description' => 'Campaign planning and release timeline',
            'department_id' => $this->hrDepartment->id,
            'access_level' => 'public',
        ]);

        $response = $this->getJson('/api/v1/documents?search=security');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('result_count', 2)
            ->assertJsonFragment(['title' => 'Information Security Handbook'])
            ->assertJsonFragment(['title' => 'Release Checklist'])
            ->assertJsonMissing(['title' => 'Marketing Launch Plan']);
    }

    public function test_user_can_filter_documents_by_category(): void
    {
        Sanctum::actingAs($this->employee);

        $this->createDocument([
            'title' => 'Employee Conduct Policy',
            'category_id' => $this->policyCategory->id,
            'access_level' => 'public',
        ]);

        $this->createDocument([
            'title' => 'Quarterly KPI Report',
            'category_id' => $this->reportCategory->id,
            'access_level' => 'public',
        ]);

        $response = $this->getJson("/api/v1/documents?category_id={$this->policyCategory->id}");

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonFragment(['title' => 'Employee Conduct Policy'])
            ->assertJsonMissing(['title' => 'Quarterly KPI Report']);
    }

    public function test_employee_only_sees_public_documents(): void
    {
        Sanctum::actingAs($this->employee);

        $this->createDocument([
            'title' => 'Public Handbook',
            'access_level' => 'public',
        ]);
        $this->createDocument([
            'title' => 'Department Strategy',
            'department_id' => $this->itDepartment->id,
            'access_level' => 'department',
        ]);
        $this->createDocument([
            'title' => 'Private Notes',
            'access_level' => 'private',
            'uploaded_by' => $this->hrManager->id,
        ]);

        $response = $this->getJson('/api/v1/documents');

        $response
            ->assertOk()
            ->assertJsonPath('result_count', 1)
            ->assertJsonFragment(['title' => 'Public Handbook'])
            ->assertJsonMissing(['title' => 'Department Strategy'])
            ->assertJsonMissing(['title' => 'Private Notes']);
    }

    public function test_manager_sees_public_and_own_department_documents_only(): void
    {
        Sanctum::actingAs($this->hrManager);

        $this->createDocument([
            'title' => 'Public Announcement',
            'department_id' => $this->itDepartment->id,
            'access_level' => 'public',
        ]);
        $this->createDocument([
            'title' => 'HR Department Plan',
            'department_id' => $this->hrDepartment->id,
            'access_level' => 'department',
        ]);
        $this->createDocument([
            'title' => 'IT Department Plan',
            'department_id' => $this->itDepartment->id,
            'access_level' => 'department',
            'uploaded_by' => $this->itManager->id,
        ]);
        $this->createDocument([
            'title' => 'IT Private Notes',
            'department_id' => $this->itDepartment->id,
            'access_level' => 'private',
            'uploaded_by' => $this->itManager->id,
        ]);

        $response = $this->getJson('/api/v1/documents');

        $response
            ->assertOk()
            ->assertJsonPath('result_count', 2)
            ->assertJsonFragment(['title' => 'Public Announcement'])
            ->assertJsonFragment(['title' => 'HR Department Plan'])
            ->assertJsonMissing(['title' => 'IT Department Plan'])
            ->assertJsonMissing(['title' => 'IT Private Notes']);
    }

    public function test_admin_can_delete_any_document(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->admin);

        $document = $this->createDocument([
            'title' => 'Delete Target',
            'uploaded_by' => $this->hrManager->id,
        ]);
        Storage::disk('local')->put($document->file_path, 'dummy content');

        $this->deleteJson("/api/v1/documents/{$document->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);
        Storage::disk('local')->assertMissing($document->file_path);
    }

    private function createDocument(array $overrides = []): Document
    {
        return Document::create(array_merge([
            'title' => 'Default Document',
            'description' => 'Default description',
            'file_name' => 'default.pdf',
            'file_path' => 'documents/default.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
            'category_id' => $this->policyCategory->id,
            'department_id' => $this->itDepartment->id,
            'uploaded_by' => $this->hrManager->id,
            'access_level' => 'public',
            'download_count' => 0,
        ], $overrides));
    }

}
