<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Facades\Voyager;

class VoyagerRoleController extends VoyagerBaseController
{
    // POST BR(E)AD
    public function update(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('edit', app($dataType->model_name));

        //Validate fields
        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();

        $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);
        $before = clone $data;
        $beforePermissions = $data->permissions()->pluck('permissions.id')->map(fn ($permissionId) => (string) $permissionId)->all();

        try {
            return DB::transaction(function () use ($request, $slug, $dataType, $data, $before, $beforePermissions) {
                $this->insertUpdateData($request, $slug, $dataType->editRows, $data);

                $permissionIds = $request->input('permissions', []);
                $data->permissions()->sync($permissionIds);

                $this->activityRecordUpdated($dataType, $before, $data);

                $afterPermissions = $data->permissions()->pluck('permissions.id')->map(fn ($permissionId) => (string) $permissionId)->all();

                foreach (array_diff($afterPermissions, $beforePermissions) as $permissionId) {
                    $this->rolePermissionEvent($data, 'attached', (string) $permissionId);
                }

                foreach (array_diff($beforePermissions, $afterPermissions) as $permissionId) {
                    $this->rolePermissionEvent($data, 'detached', (string) $permissionId);
                }

                return redirect()
                    ->route("voyager.{$dataType->slug}.index")
                    ->with([
                        'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                        'alert-type' => 'success',
                    ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with([
                'message'    => __('voyager::activity.save_failed'),
                'alert-type' => 'error',
            ]);
        }
    }

    // POST BRE(A)D
    public function store(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        //Validate fields
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();

        try {
            return DB::transaction(function () use ($request, $slug, $dataType) {
                $data = new $dataType->model_name();
                $this->insertUpdateData($request, $slug, $dataType->addRows, $data);

                $permissionIds = $request->input('permissions', []);
                $data->permissions()->sync($permissionIds);

                $this->activityRecordCreated($dataType, $data);

                foreach ($permissionIds as $permissionId) {
                    $this->rolePermissionEvent($data, 'attached', (string) $permissionId);
                }

                return redirect()
                    ->route("voyager.{$dataType->slug}.index")
                    ->with([
                        'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                        'alert-type' => 'success',
                    ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with([
                'message'    => __('voyager::activity.save_failed'),
                'alert-type' => 'error',
            ]);
        }
    }

    protected function rolePermissionEvent($role, string $action, string $permissionId): void
    {
        $this->activity()->recordEventSafe(
            'role.permission_'.$action,
            'success',
            $role,
            'roles',
            [],
            ['permission_id' => $permissionId],
            'role',
            (string) $role->getKey()
        );
    }
}
