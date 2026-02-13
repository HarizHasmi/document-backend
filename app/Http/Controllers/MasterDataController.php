<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Models\Category;

class MasterDataController extends Controller
{
    public function departments()
    {
        return DepartmentResource::collection(Department::query()->orderBy('name')->get())
            ->additional([
                'status' => 'success',
                'message' => 'Departments retrieved successfully',
            ]);
    }

    public function categories()
    {
        return CategoryResource::collection(Category::query()->orderBy('title')->get())
            ->additional([
                'status' => 'success',
                'message' => 'Categories retrieved successfully',
            ]);
    }
}

