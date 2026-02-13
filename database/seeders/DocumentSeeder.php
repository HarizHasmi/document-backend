<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::query()->pluck('id', 'name');

        $admin = User::query()->role('admin')->first();
        $managersByDepartment = User::query()
            ->role('manager')
            ->get()
            ->keyBy('department_id');

        if (!$admin || $departments->isEmpty()) {
            return;
        }

        Document::query()->delete();
        Storage::disk('local')->deleteDirectory('documents/seeded');

        $departmentShortNames = [
            'Human Resources (HR)' => 'HR',
            'Finance' => 'Finance',
            'Information Technology (IT)' => 'IT',
            'Marketing' => 'Marketing',
            'Operations' => 'Operations',
        ];

        $templates = [
            [
                'title' => ':short Department Policy Manual 2026',
                'description' => 'Official policy manual for :department team members.',
                'category' => 'Policy',
            ],
            [
                'title' => ':short Monthly Performance Report',
                'description' => 'Monthly performance report prepared by the :department team.',
                'category' => 'Report',
            ],
            [
                'title' => ':short Standard Operating Procedure Template',
                'description' => 'Reusable SOP template for :department processes.',
                'category' => 'Template',
            ],
            [
                'title' => ':short Employee Workflow Guide',
                'description' => 'Step-by-step guide for common :department workflows.',
                'category' => 'Guide',
            ],
            [
                'title' => ':short Internal Request Form',
                'description' => 'Internal request form used by the :department department.',
                'category' => 'Form',
            ],
            [
                'title' => ':short Operations Reference Notes',
                'description' => 'Reference notes and supporting materials for :department tasks.',
                'category' => 'Other',
            ],
        ];

        $accessByDepartment = [
            'Human Resources (HR)' => ['public', 'public', 'public', 'department', 'department', 'private'],
            'Finance' => ['public', 'public', 'public', 'department', 'department', 'private'],
            'Information Technology (IT)' => ['public', 'public', 'public', 'department', 'department', 'private'],
            'Marketing' => ['public', 'public', 'public', 'department', 'department', 'private'],
            'Operations' => ['public', 'public', 'public', 'department', 'private', 'private'],
        ];

        $categoryFactoryStateByTitle = [
            'Policy' => 'policy',
            'Report' => 'report',
            'Template' => 'template',
            'Guide' => 'guide',
            'Form' => 'form',
            'Other' => 'other',
        ];
        $accessFactoryStateByLevel = [
            'public' => 'publicAccess',
            'department' => 'departmentAccess',
            'private' => 'privateAccess',
        ];

        $extensions = ['pdf', 'docx', 'xlsx', 'jpg', 'png'];

        $documentIndex = 0;
        foreach ($departmentShortNames as $departmentName => $shortName) {
            $departmentId = $departments[$departmentName] ?? null;
            if (!$departmentId) {
                continue;
            }

            $manager = $managersByDepartment[$departmentId] ?? null;
            $departmentAccess = $accessByDepartment[$departmentName];

            foreach ($templates as $templateIndex => $template) {
                $title = str_replace(
                    [':short', ':department'],
                    [$shortName, $departmentName],
                    $template['title']
                );
                $description = str_replace(
                    [':short', ':department'],
                    [$shortName, $departmentName],
                    $template['description']
                );

                $extension = $extensions[$documentIndex % count($extensions)];
                $slug = Str::slug($title);
                $fileName = "{$slug}.{$extension}";
                $filePath = "documents/seeded/{$fileName}";
                $fileContents = "Dummy {$extension} file for {$title}";

                $preferredUploaderId = $manager?->id ?? $admin->id;
                $uploadedBy = $documentIndex % 3 === 0
                    ? $admin->id
                    : $preferredUploaderId;

                $categoryFactoryState = $categoryFactoryStateByTitle[$template['category']] ?? null;
                $accessFactoryState = $accessFactoryStateByLevel[$departmentAccess[$templateIndex]] ?? null;
                if (!$categoryFactoryState || !$accessFactoryState) {
                    continue;
                }

                Document::factory()
                    ->forDepartment((int) $departmentId)
                    ->uploadedBy((int) $uploadedBy)
                    ->titled($title, $description)
                    ->{$categoryFactoryState}()
                    ->{$accessFactoryState}()
                    ->state([
                        'file_name' => $fileName,
                        'file_path' => $filePath,
                        'file_type' => $extension,
                        'file_size' => strlen($fileContents),
                        'download_count' => 0,
                    ])
                    ->create();

                Storage::disk('local')->put($filePath, $fileContents);

                $documentIndex++;
            }
        }
    }
}
