<?php

namespace TCG\Voyager\Support;

use TCG\Voyager\Models\DataRow;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;
use TCG\Voyager\Models\Permission;
use TCG\Voyager\Models\Role;

class GalleryInstaller
{
    public function install(): array
    {
        if (!config('voyager.gallery.enabled', true)) {
            return ['enabled' => false];
        }

        $dataType = $this->installDataType();
        $this->installRows($dataType);
        $this->installPermissions();
        $menuItem = $this->installMenuItem();

        return [
            'enabled'    => true,
            'data_type'  => $dataType->id,
            'menu_item'  => $menuItem?->id,
        ];
    }

    protected function installDataType(): DataType
    {
        $dataType = DataType::firstOrNew(['slug' => 'galleries']);

        if (!$dataType->exists) {
            $dataType->fill([
                'name'                  => 'galleries',
                'display_name_singular' => $this->translate('voyager::seeders.data_types.gallery.singular', 'Gallery'),
                'display_name_plural'   => $this->translate('voyager::seeders.data_types.gallery.plural', 'Galleries'),
                'icon'                  => 'voyager-photos',
                'model_name'            => 'TCG\\Voyager\\Models\\Gallery',
                'controller'            => 'TCG\\Voyager\\Http\\Controllers\\VoyagerGalleryController',
                'generate_permissions'  => 1,
                'server_side'           => 1,
                'description'           => 'Photo galleries embedded into content with %gallery="Title"%',
                'details'               => (object) [],
            ]);

            $dataType->save();
        }

        return $dataType;
    }

    protected function installRows(DataType $dataType): void
    {
        $rows = [
            'id' => [
                'type'         => 'number',
                'display_name' => $this->translate('voyager::seeders.data_rows.id', 'ID'),
                'required'     => 0,
                'browse'       => 0,
                'read'         => 0,
                'edit'         => 0,
                'add'          => 0,
                'delete'       => 0,
                'order'        => 1,
            ],
            'title' => [
                'type'         => 'text',
                'display_name' => $this->translate('voyager::seeders.data_rows.title', 'Title'),
                'required'     => 1,
                'browse'       => 1,
                'read'         => 1,
                'edit'         => 1,
                'add'          => 1,
                'delete'       => 1,
                'order'        => 2,
            ],
            'dimensions' => [
                'type'         => 'select_dropdown',
                'display_name' => $this->translate('voyager::seeders.gallery.dimensions', 'Thumbnail size'),
                'required'     => 0,
                'browse'       => 0,
                'read'         => 1,
                'edit'         => 1,
                'add'          => 1,
                'delete'       => 1,
                'order'        => 3,
                'details'      => [
                    'default' => config('voyager.gallery.thumb_dimensions', '400x300'),
                    'options' => [
                        '400x300' => '400 × 300 (4:3)',
                        '600x450' => '600 × 450 (4:3)',
                        '800x600' => '800 × 600 (4:3)',
                        '1024x768' => '1024 × 768 (4:3)',
                        '300x400' => '300 × 400 (3:4)',
                        '450x600' => '450 × 600 (3:4)',
                        '600x800' => '600 × 800 (3:4)',
                        '768x1024' => '768 × 1024 (3:4)',
                        '300x300' => '300 × 300',
                        '500x500' => '500 × 500',
                        '800x800' => '800 × 800',
                        '600x400' => '600 × 400 (3:2)',
                        '400x600' => '400 × 600 (2:3)',
                        '900x600' => '900 × 600 (3:2)',
                        '600x900' => '600 × 900 (2:3)',
                    ],
                ],
            ],
            'description' => [
                'type'         => 'text_area',
                'display_name' => $this->translate('voyager::seeders.gallery.description', 'Description'),
                'required'     => 0,
                'browse'       => 0,
                'read'         => 1,
                'edit'         => 1,
                'add'          => 1,
                'delete'       => 1,
                'order'        => 4,
            ],
            'sort_order' => [
                'type'         => 'number',
                'display_name' => $this->translate('voyager::seeders.gallery.sort_order', 'Sort order'),
                'required'     => 0,
                'browse'       => 1,
                'read'         => 1,
                'edit'         => 1,
                'add'          => 1,
                'delete'       => 1,
                'order'        => 5,
            ],
            'created_at' => [
                'type'         => 'timestamp',
                'display_name' => $this->translate('voyager::seeders.data_rows.created_at', 'Created At'),
                'required'     => 0,
                'browse'       => 0,
                'read'         => 0,
                'edit'         => 0,
                'add'          => 0,
                'delete'       => 0,
                'order'        => 6,
            ],
            'updated_at' => [
                'type'         => 'timestamp',
                'display_name' => $this->translate('voyager::seeders.data_rows.updated_at', 'Updated At'),
                'required'     => 0,
                'browse'       => 0,
                'read'         => 0,
                'edit'         => 0,
                'add'          => 0,
                'delete'       => 0,
                'order'        => 7,
            ],
        ];

        foreach ($rows as $field => $values) {
            $row = DataRow::firstOrNew([
                'data_type_id' => $dataType->id,
                'field'        => $field,
            ]);

            if (!$row->exists) {
                $row->fill($values + ['field' => $field]);
                $row->save();
            }
        }
    }

    protected function installPermissions(): void
    {
        Permission::generateFor('galleries');

        $role = Role::where('name', 'admin')->first();
        if (!$role) {
            return;
        }

        $permissions = Permission::where('table_name', 'galleries')->pluck('id')->all();
        if ($permissions) {
            $role->permissions()->syncWithoutDetaching($permissions);
        }
    }

    protected function installMenuItem(): ?MenuItem
    {
        $menu = Menu::where('name', 'admin')->first();
        if (!$menu) {
            return null;
        }

        $menuItem = MenuItem::firstOrNew([
            'menu_id' => $menu->id,
            'route'   => 'voyager.galleries.index',
        ]);

        if (!$menuItem->exists) {
            $title = config('voyager.gallery.menu_title')
                ?: $this->translate('voyager::seeders.menu_items.galleries', 'Galleries');

            $menuItem->fill([
                'title'      => $title,
                'url'        => '',
                'target'     => '_self',
                'icon_class' => 'voyager-photos',
                'color'      => null,
                'parent_id'  => null,
                'order'      => ((int) MenuItem::where('menu_id', $menu->id)->max('order')) + 1,
            ]);

            $menuItem->save();
        }

        return $menuItem;
    }

    protected function translate(string $key, string $fallback): string
    {
        $value = trans($key, [], app()->getLocale());
        if ($value !== $key) {
            return $value;
        }

        $value = trans($key, [], 'en');

        return $value === $key ? $fallback : $value;
    }
}
