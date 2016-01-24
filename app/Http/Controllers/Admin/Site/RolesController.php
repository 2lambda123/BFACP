<?php

namespace BFACP\Http\Controllers\Admin\Site;

use BFACP\Account\Permission;
use BFACP\Account\Role;
use BFACP\Facades\Main as MainHelper;
use BFACP\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

/**
 * Class RolesController.
 */
class RolesController extends Controller
{
    /**
     * @return mixed
     */
    public function index()
    {
        $roles = Role::with('users')->get();

        $page_title = trans('navigation.admin.site.items.roles.title');

        return view('admin.site.roles.index', compact('roles', 'page_title'));
    }

    /**
     * @return mixed
     */
    public function create()
    {
        $permissions = [];

        foreach (Permission::all() as $permission) {
            if (preg_match('/^admin\\.([a-z]+)/A', $permission->name, $matches)) {

                // Uppercase the first letter
                $key = ucfirst($matches[1]);

                // Push to array
                $permissions[$key][$permission->id] = $permission->display_name;
            } else {

                // Push to array
                $permissions['General'][$permission->id] = $permission->display_name;
            }
        }

        $page_title = trans('navigation.admin.site.items.roles.items.create.title');

        return view('admin.site.roles.create', compact('permissions', 'page_title'));
    }

    /**
     * @return mixed
     */
    public function store()
    {
        try {
            $role = new Role();

            $permissions = new Collection(Input::get('permissions', []));

            if (Input::has('permissions')) {
                $permissions = $permissions->filter(function ($id) {
                    if (is_numeric($id)) {
                        return true;
                    }
                })->map(function ($id) {
                    return (int) $id;
                });
            }

            $v = Validator::make(Input::all(), [
                'role_name' => Role::$rules['name'],
            ]);

            if ($v->fails()) {
                return redirect()->route('admin.site.roles.create')->withErrors($v)->withInput();
            }

            $role->name = trim(Input::get('role_name'));
            $role->save();

            // Update role permissions
            $role->permissions()->sync($permissions->toArray());

            return redirect()->route('admin.site.roles.edit', [$role->id])->with('messages', [
                'Role Created!',
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.site.roles.index');
        }
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function edit($id)
    {
        try {
            $role = Role::with('users', 'permissions')->findOrFail($id);

            $permissions = [];

            foreach (Permission::all() as $permission) {
                if (preg_match('/^admin\\.([a-z]+)/A', $permission->name, $matches)) {

                    // Uppercase the first letter
                    $key = ucfirst($matches[1]);

                    // Push to array
                    $permissions[$key][$permission->id] = $permission->display_name;
                } else {

                    // Push to array
                    $permissions['General'][$permission->id] = $permission->display_name;
                }
            }

            $page_title = trans('navigation.admin.site.items.roles.items.edit.title', ['name' => $role->name]);

            return view('admin.site.roles.edit', compact('role', 'permissions', 'page_title'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.site.roles.index')->withErrors([sprintf('Role #%u doesn\'t exist.', $id)]);
        }
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function update($id)
    {
        try {
            // Disable rules on model
            Role::$rules = [];

            $role = Role::findOrFail($id);

            $permissions = new Collection(Input::get('permissions', []));

            if (Input::has('permissions')) {
                $permissions = $permissions->filter(function ($id) {
                    if (is_numeric($id)) {
                        return true;
                    }
                })->map(function ($id) {
                    return (int) $id;
                });
            }

            // Update role permissions
            $role->permissions()->sync($permissions->toArray());

            if (Input::get('display_name') != $role->name && ! in_array($role->id, [1, 2])) {
                $role->name = trim(Input::get('display_name'));
                $role->save();
            } else {
                // Update timestamp
                $role->touch();
            }

            return redirect()->route('admin.site.roles.edit', [$id])->with('messages', [
                'Role Updated!',
            ]);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.site.roles.index')->withErrors([sprintf('Role #%u doesn\'t exist.', $id)]);
        }
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function destroy($id)
    {
        try {
            // Disable rules on model
            Role::$rules = [];

            // Get role
            $role = Role::findOrFail($id);

            if (in_array($role->id, [1, 2])) {
                return MainHelper::response(null, sprintf('You can\'t delete the %s role.', $role->name), 'error');
            }

            // Save role name
            $roleName = $role->name;

            foreach ($role->users as $user) {
                $user->roles()->detach($id);
                $user->roles()->attach(2);
            }

            $role->delete();

            return MainHelper::response([
                'url' => route('admin.site.roles.index'),
            ], sprintf('%s was deleted', $roleName));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.site.roles.index')->withErrors([sprintf('Role #%u doesn\'t exist.', $id)]);
        }
    }
}