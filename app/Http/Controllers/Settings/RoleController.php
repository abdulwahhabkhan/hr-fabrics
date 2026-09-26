<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $roles = Role::filter($request)->paginate(100);

        return Inertia::render(
            'Settings/Role/RoleIndex',
            [
                'roles' => $roles,
                'filters' => $request->only('search'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $permissions = Permission::listPermissions();

        return Inertia::render(
            'Settings/Role/RoleForm',
            [
                'permissions' => array_values($permissions->toArray()),
                'role' => '',
                'rolePermission' => [],
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request): RedirectResponse
    {
        $request->validated();
        $data = $request->only('name', 'description');
        $role = Role::create($data);
        $permissions = $request->only(['permissions']);
        $role->permissions()->attach($permissions['permissions']);

        return Redirect::route('settings.roles.index')->with(['success' => 'Role created successfully']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): Response
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::listPermissions();

        return Inertia::render(
            'Settings/Role/RoleForm',
            [
                'permissions' => array_values($permissions->toArray()),
                'role' => $role,
                'rolePermission' => $role->permissions->pluck(['id']),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, int $id): RedirectResponse
    {
        $request->validated();
        $data = $request->only('name', 'description');
        $role = Role::find($id);
        $role->update($data);
        Role::clearCache($id);
        $permissions = $request->only('permissions');
        $role->permissions()->sync($permissions['permissions']);

        return Redirect::route('settings.roles.index')->with(['success' => 'Role updated successfully']);
    }
}
