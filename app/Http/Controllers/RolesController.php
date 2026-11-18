<?php

namespace App\Http\Controllers;

use App\Interfaces\ModelSearchBuilder;
use App\Models\Role;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function index(ModelSearchBuilder $search, Request $request)
    {
        return $search
            ->init(Role::class, $request)
            ->inColumn('name')
            ->search()
            ->paginate();
    }
}
