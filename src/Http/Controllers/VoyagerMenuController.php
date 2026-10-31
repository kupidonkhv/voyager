<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Activity\ActivityDiff;
use TCG\Voyager\Facades\Voyager;

class VoyagerMenuController extends Controller
{
    public function builder($id)
    {
        $menu = Voyager::model('Menu')->findOrFail($id);

        $this->authorize('edit', $menu);

        $isModelTranslatable = is_bread_translatable(Voyager::model('MenuItem'));

        return Voyager::view('voyager::menus.builder', compact('menu', 'isModelTranslatable'));
    }

    public function delete_menu($menu, $id)
    {
        $item = Voyager::model('MenuItem')->findOrFail($id);

        $this->authorize('delete', $item);

        try {
            return DB::transaction(function () use ($menu, $id, $item) {
                $item->deleteAttributeTranslation('title');
                $item->destroy($id);

                $this->activity()->recordEventSafe(
                    'menu.item_deleted',
                    'success',
                    $item,
                    'menus',
                    [],
                    ['menu_id' => (string) $menu],
                    'menu_item',
                    (string) $id
                );

                return redirect()
                    ->route('voyager.menus.builder', [$menu])
                    ->with([
                        'message'    => __('voyager::menu_builder.successfully_deleted'),
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

    public function add_item(Request $request)
    {
        $menu = Voyager::model('Menu');

        $this->authorize('add', $menu);

        $data = $this->prepareParameters(
            $request->all()
        );

        unset($data['id']);
        $data['order'] = Voyager::model('MenuItem')->highestOrderMenuItem();

        // Check if is translatable
        $_isTranslatable = is_bread_translatable(Voyager::model('MenuItem'));
        $trans = [];
        if ($_isTranslatable) {
            // Prepare data before saving the menu
            $trans = $this->prepareMenuTranslations($data);
        }

        try {
            return DB::transaction(function () use ($data, $trans, $_isTranslatable) {
                $menuItem = Voyager::model('MenuItem')->create($data);

                // Save menu translations
                if ($_isTranslatable) {
                    $menuItem->setAttributeTranslations('title', $trans, true);
                }

                $this->activity()->recordEventSafe(
                    'menu.item_created',
                    'success',
                    $menuItem,
                    'menus',
                    [],
                    ['menu_id' => (string) $menuItem->menu_id],
                    'menu_item',
                    (string) $menuItem->getKey()
                );

                return redirect()
                    ->route('voyager.menus.builder', [$data['menu_id']])
                    ->with([
                        'message'    => __('voyager::menu_builder.successfully_created'),
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

    public function update_item(Request $request)
    {
        $id = $request->input('id');
        $data = $this->prepareParameters(
            $request->except(['id'])
        );

        $menuItem = Voyager::model('MenuItem')->findOrFail($id);

        $this->authorize('edit', $menuItem->menu);

        $before = clone $menuItem;
        if (method_exists($before, 'translations')) {
            $before->loadMissing('translations');
        }

        try {
            return DB::transaction(function () use ($data, $menuItem, $before) {
                if (is_bread_translatable($menuItem)) {
                    $trans = $this->prepareMenuTranslations($data);

                    // Save menu translations
                    $menuItem->setAttributeTranslations('title', $trans, true);
                }

                $menuItem->update($data);

                $changed = ActivityDiff::changedFields($before, $menuItem, $this->activityExcludedFields());
                if (method_exists($before, 'translations')) {
                    $changed = array_values(array_unique(array_merge(
                        $changed,
                        ActivityDiff::translationFields($before, $menuItem, 'translations', $this->activityExcludedFields())
                    )));
                    sort($changed, SORT_STRING);
                }

                if ($changed) {
                    $this->activity()->recordEventSafe(
                        'menu.item_updated',
                        'success',
                        $menuItem,
                        'menus',
                        $changed,
                        ['menu_id' => (string) $menuItem->menu_id],
                        'menu_item',
                        (string) $menuItem->getKey()
                    );
                }

                return redirect()
                    ->route('voyager.menus.builder', [$menuItem->menu_id])
                    ->with([
                        'message'    => __('voyager::menu_builder.successfully_updated'),
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

    public function order_item(Request $request)
    {
        $menuItemOrder = json_decode($request->input('order'));

        if (!is_array($menuItemOrder)) {
            return;
        }

        DB::transaction(function () use ($menuItemOrder, $request) {
            $count = $this->orderMenu($menuItemOrder, null);
            $menuId = $request->route('menu');

            $this->activity()->recordEventSafe(
                'menu.reordered',
                'success',
                null,
                'menus',
                [],
                ['count' => $count, 'menu_id' => (string) $menuId],
                'menu',
                (string) $menuId
            );
        });
    }

    private function orderMenu(array $menuItems, $parentId): int
    {
        $count = 0;

        foreach ($menuItems as $index => $menuItem) {
            $item = Voyager::model('MenuItem')->findOrFail($menuItem->id);
            $item->order = $index + 1;
            $item->parent_id = $parentId;
            $item->save();
            $count++;

            if (isset($menuItem->children)) {
                $count += $this->orderMenu($menuItem->children, $item->id);
            }
        }

        return $count;
    }

    protected function prepareParameters($parameters)
    {
        switch (Arr::get($parameters, 'type')) {
            case 'route':
                $parameters['url'] = null;
                break;
            default:
                $parameters['route'] = null;
                $parameters['parameters'] = '';
                break;
        }

        if (isset($parameters['type'])) {
            unset($parameters['type']);
        }

        return $parameters;
    }

    /**
     * Prepare menu translations.
     *
     * @param array $data menu data
     *
     * @return JSON translated item
     */
    protected function prepareMenuTranslations(&$data)
    {
        $trans = json_decode($data['title_i18n'], true);

        // Set field value with the default locale
        $data['title'] = $trans[config('voyager.multilingual.default', 'en')];

        unset($data['title_i18n']);     // Remove hidden input holding translations
        unset($data['i18n_selector']);  // Remove language selector input radio

        return $trans;
    }
}
