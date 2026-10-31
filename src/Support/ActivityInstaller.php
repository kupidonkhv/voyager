<?php

namespace TCG\Voyager\Support;

use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;
use TCG\Voyager\Models\Permission;
use TCG\Voyager\Models\Role;

class ActivityInstaller
{
    public const PERMISSIONS = [
        'browse_activity_logs',
        'browse_activity_log_context',
    ];

    public function install(): array
    {
        $permissionIds = [];

        foreach (self::PERMISSIONS as $key) {
            $permission = Permission::firstOrCreate(
                ['key' => $key],
                ['table_name' => null]
            );

            $permissionIds[] = $permission->id;
        }

        $role = Role::where('name', 'admin')->first();
        if ($role) {
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }

        $menuItem = $this->installMenuItem();

        return [
            'permissions' => $permissionIds,
            'menu_item'   => $menuItem?->id,
        ];
    }

    protected function installMenuItem(): ?MenuItem
    {
        $menu = Menu::where('name', 'admin')->first();
        if (!$menu) {
            return null;
        }

        $menuItem = MenuItem::firstOrNew([
            'menu_id' => $menu->id,
            'route'   => 'voyager.activity.index',
        ]);

        if (!$menuItem->exists) {
            $menuItem->fill([
                'title'      => trans('voyager::activity.menu_title'),
                'url'        => '',
                'target'     => '_self',
                'icon_class' => 'voyager-list',
                'color'      => null,
                'parent_id'  => null,
                'order'      => ((int) MenuItem::where('menu_id', $menu->id)->max('order')) + 1,
            ]);

            $menuItem->save();
        }

        return $menuItem;
    }
}
