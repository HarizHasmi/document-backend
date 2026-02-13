<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected static int $sequence = 1;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = self::$sequence++;
        $documentNumber = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        $departmentId = $this->resolveDepartmentId();
        $categoryId = $this->resolveCategoryId(
            'Policy',
            'Official policies and compliance documents.'
        );

        return [
            'title' => "Factory Document {$documentNumber}",
            'description' => "Deterministic description for factory document {$documentNumber}.",
            'file_name' => "factory-document-{$documentNumber}.pdf",
            'file_path' => "documents/factory/factory-document-{$documentNumber}.pdf",
            'file_type' => 'pdf',
            'file_size' => 2000 + $sequence,
            'category_id' => $categoryId,
            'department_id' => $departmentId,
            'uploaded_by' => $this->resolveUploaderId($departmentId),
            'access_level' => 'public',
            'download_count' => 0,
        ];
    }

    public function forDepartment(int $departmentId): static
    {
        return $this->state(fn () => [
            'department_id' => $departmentId,
            'uploaded_by' => $this->resolveUploaderId($departmentId),
        ]);
    }

    public function uploadedBy(int|User $user): static
    {
        $uploaderId = $user instanceof User ? $user->id : $user;

        return $this->state(fn () => [
            'uploaded_by' => $uploaderId,
        ]);
    }

    public function publicAccess(): static
    {
        return $this->withAccessLevel('public');
    }

    public function departmentAccess(): static
    {
        return $this->withAccessLevel('department');
    }

    public function privateAccess(): static
    {
        return $this->withAccessLevel('private');
    }

    public function policy(): static
    {
        return $this->withCategory(
            'Policy',
            'Official policies and compliance documents.'
        );
    }

    public function report(): static
    {
        return $this->withCategory(
            'Report',
            'Operational and performance reporting documents.'
        );
    }

    public function template(): static
    {
        return $this->withCategory(
            'Template',
            'Reusable templates for recurring tasks.'
        );
    }

    public function guide(): static
    {
        return $this->withCategory(
            'Guide',
            'Instructional guides and process walkthroughs.'
        );
    }

    public function form(): static
    {
        return $this->withCategory(
            'Form',
            'Forms used for requests and internal workflows.'
        );
    }

    public function other(): static
    {
        return $this->withCategory(
            'Other',
            'Other supporting documents not in standard categories.'
        );
    }

    public function titled(string $title, ?string $description = null): static
    {
        return $this->state(fn () => [
            'title' => $title,
            'description' => $description ?? "Description for {$title}.",
        ]);
    }

    public function fileType(string $fileType): static
    {
        $allowed = ['pdf', 'docx', 'xlsx', 'jpg', 'png'];
        if (!in_array($fileType, $allowed, true)) {
            throw new InvalidArgumentException(
                "Unsupported file type [{$fileType}] for document factory."
            );
        }

        return $this->state(function (array $attributes) use ($fileType): array {
            $baseName = pathinfo(
                (string) ($attributes['file_name'] ?? 'factory-document'),
                PATHINFO_FILENAME
            );
            $normalizedName = $baseName !== '' ? $baseName : 'factory-document';

            return [
                'file_name' => "{$normalizedName}.{$fileType}",
                'file_path' => "documents/factory/{$normalizedName}.{$fileType}",
                'file_type' => $fileType,
            ];
        });
    }

    private function withCategory(string $title, string $description): static
    {
        $categoryId = $this->resolveCategoryId($title, $description);

        return $this->state(fn () => [
            'category_id' => $categoryId,
        ]);
    }

    private function withAccessLevel(string $accessLevel): static
    {
        $allowed = ['public', 'department', 'private'];
        if (!in_array($accessLevel, $allowed, true)) {
            throw new InvalidArgumentException(
                "Unsupported access level [{$accessLevel}] for document factory."
            );
        }

        return $this->state(fn () => [
            'access_level' => $accessLevel,
        ]);
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

    private function resolveCategoryId(string $title, string $description): int
    {
        $existingCategoryId = Category::query()
            ->where('title', $title)
            ->value('id');
        if ($existingCategoryId) {
            return (int) $existingCategoryId;
        }

        return Category::query()
            ->firstOrCreate(
                ['title' => $title],
                ['description' => $description]
            )
            ->id;
    }

    private function resolveUploaderId(int $departmentId): int
    {
        $existingUploaderId = User::query()
            ->where('department_id', $departmentId)
            ->value('id');
        if ($existingUploaderId) {
            return (int) $existingUploaderId;
        }

        return User::factory()
            ->inDepartment($departmentId)
            ->create()
            ->id;
    }
}
