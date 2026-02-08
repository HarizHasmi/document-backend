<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Document::query()
            ->when($request->search, fn($q)=>
                $q->where('title','ilike',"%{$request->search}%")
                  ->orWhere('description','ilike',"%{$request->search}%")
            )
            ->when($request->category_id, fn($q)=>$q->where('category_id',$request->category_id))
            ->when($request->department_id, fn($q)=>$q->where('department_id',$request->department_id))
            ->paginate(10);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create',Document::class);

        $data = $request->validate([
            'title'=>'required',
            'file'=>'required|file|max:10240|mimes:pdf,docx,xlsx,jpg,png',
            'category_id'=>'required',
            'department_id'=>'required',
            'access_level'=>'required|in:public,department,private',
        ]);

        $file = $request->file('file');

        return Document::create([
            'title'=>$data['title'],
            'file_name'=>$file->getClientOriginalName(),
            'file_path'=>$file->store('documents'),
            'file_type'=>$file->extension(),
            'file_size'=>$file->getSize(),
            'category_id'=>$data['category_id'],
            'department_id'=>$data['department_id'],
            'access_level'=>$data['access_level'],
            'uploaded_by'=>auth()->id(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        $this->authorize('view',$document);
        return $document;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Document $document)
    {
        $this->authorize('update',$document);

        $document->update(
            $request->only('title','description','category_id','access_level')
        );

        return $document;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document)
    {
        $this->authorize('delete',$document);

        Storage::delete($document->file_path);
        $document->delete();

        return ['message'=>'Deleted'];
    }

    public function download(Document $document)
    {
        $this->authorize('view',$document);

        $document->increment('download_count');

        return Storage::download(
            $document->file_path,
            $document->file_name
        );
    }
}
