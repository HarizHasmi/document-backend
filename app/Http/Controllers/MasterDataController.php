<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Category;

class MasterDataController extends Controller
{
    public function departments()
    {
        return Department::all();
    }

    public function categories()
    {
        return Category::all();
    }
}

