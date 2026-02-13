<?php

namespace App\Http\Controllers;

use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Document::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $documents = $this->visibleDocumentsQuery($request->user())
            ->with(['category', 'department', 'uploader'])
            ->when($validated['search'] ?? null, function (Builder $query, string $search): void {
                $keyword = strtolower($search);
                $query->where(function (Builder $searchQuery) use ($keyword): void {
                    $searchQuery->whereRaw('LOWER(title) LIKE ?', ["%{$keyword}%"])
                        ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', ["%{$keyword}%"]);
                });
            })
            ->when(
                $validated['category_id'] ?? null,
                fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId)
            )
            ->when(
                $validated['department_id'] ?? null,
                fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId)
            )
            ->latest()
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return DocumentResource::collection($documents)
            ->additional([
                'status' => 'success',
                'message' => 'Documents retrieved successfully',
                'result_count' => $documents->total(),
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Document::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,docx,xlsx,jpg,png'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'access_level' => ['required', 'in:public,department,private'],
        ]);

        $user = $request->user();

        if ($user->hasRole('manager') && $user->department_id !== (int) $data['department_id']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Managers can only upload documents to their own department',
            ], 403);
        }

        $file = $request->file('file');
        $filePath = $file->store('documents', 'local');

        $document = Document::create([
            'title'=>$data['title'],
            'description' => $data['description'] ?? null,
            'file_name'=>$file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type'=>$file->getClientOriginalExtension(),
            'file_size'=>$file->getSize(),
            'category_id'=>$data['category_id'],
            'department_id'=>$data['department_id'],
            'access_level'=>$data['access_level'],
            'uploaded_by'=>$user->id,
        ]);

        $document->load(['category', 'department', 'uploader']);

        return response()->json([
            'status' => 'success',
            'message' => 'Document uploaded successfully',
            'data' => new DocumentResource($document),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        $this->authorize('view', $document);
        $document->load(['category', 'department', 'uploader']);

        return response()->json([
            'status' => 'success',
            'message' => 'Document retrieved successfully',
            'data' => new DocumentResource($document),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'department_id' => ['sometimes', 'required', 'integer', 'exists:departments,id'],
            'access_level' => ['sometimes', 'required', 'in:public,department,private'],
        ]);

        $user = $request->user();
        if (
            $user->hasRole('manager') &&
            array_key_exists('department_id', $data) &&
            $user->department_id !== (int) $data['department_id']
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Managers can only keep documents in their own department',
            ], 403);
        }

        $document->update($data);
        $document->load(['category', 'department', 'uploader']);

        return response()->json([
            'status' => 'success',
            'message' => 'Document updated successfully',
            'data' => new DocumentResource($document),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Document deleted successfully',
        ]);
    }

    public function download(Document $document)
    {
        $this->authorize('view', $document);

        if (!Storage::disk('local')->exists($document->file_path)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File not found',
            ], 404);
        }

        $document->increment('download_count');

        return Storage::disk('local')->download(
            $document->file_path,
            $document->file_name
        );
    }

    private function visibleDocumentsQuery(User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return Document::query();
        }

        if ($user->hasRole('manager')) {
            return Document::query()
                ->where(function (Builder $query) use ($user): void {
                    $query->where('access_level', 'public')
                        ->orWhere(function (Builder $departmentQuery) use ($user): void {
                            $departmentQuery
                                ->where('access_level', 'department')
                                ->where('department_id', $user->department_id);
                        })
                        ->orWhere('uploaded_by', $user->id);
                });
        }

        return Document::query()->where('access_level', 'public');
    }
}
