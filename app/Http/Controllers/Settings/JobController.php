<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $roles = Role::filter($request)->paginate(100);

        return Inertia::render('Settings/Role/RoleIndex',
            [
                'roles' => $roles,
                'filters' => $request->only('search'),
            ]);
    }
}
