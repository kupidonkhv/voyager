<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

class VoyagerSettingsController extends Controller
{
    public function index()
    {
        // Check permission
        $this->authorize('browse', Voyager::model('Setting'));

        $data = Voyager::model('Setting')->orderBy('order', 'ASC')->get();

        $settings = [];
        $settings[__('voyager::settings.group_general')] = [];
        foreach ($data as $d) {
            if ($d->group == '' || $d->group == __('voyager::settings.group_general')) {
                $settings[__('voyager::settings.group_general')][] = $d;
            } else {
                $settings[$d->group][] = $d;
            }
        }
        if (count($settings[__('voyager::settings.group_general')]) == 0) {
            unset($settings[__('voyager::settings.group_general')]);
        }

        $groups_data = Voyager::model('Setting')->select('group')->distinct()->get();
        $groups = [];
        foreach ($groups_data as $group) {
            if ($group->group != '') {
                $groups[] = $group->group;
            }
        }

        $active = (request()->session()->has('setting_tab')) ? request()->session()->get('setting_tab') : old('setting_tab', key($settings));

        return Voyager::view('voyager::settings.index', compact('settings', 'groups', 'active'));
    }

    public function store(Request $request)
    {
        // Check permission
        $this->authorize('add', Voyager::model('Setting'));

        $key = implode('.', [Str::slug($request->input('group')), $request->input('key')]);
        $key_check = Voyager::model('Setting')->where('key', $key)->get()->count();

        if ($key_check > 0) {
            return back()->with([
                'message'    => __('voyager::settings.key_already_exists', ['key' => $key]),
                'alert-type' => 'error',
            ]);
        }

        $lastSetting = Voyager::model('Setting')->orderBy('order', 'DESC')->first();

        if (is_null($lastSetting)) {
            $order = 0;
        } else {
            $order = intval($lastSetting->order) + 1;
        }

        $request->merge(['order' => $order]);
        $request->merge(['value' => '']);
        $request->merge(['key' => $key]);

        Voyager::model('Setting')->create($request->except('setting_tab'));

        request()->flashOnly('setting_tab');

        return back()->with([
            'message'    => __('voyager::settings.successfully_created'),
            'alert-type' => 'success',
        ]);
    }

    public function update(Request $request)
    {
        // Check permission
        $this->authorize('edit', Voyager::model('Setting'));

        $settings = Voyager::model('Setting')->all();

        foreach ($settings as $setting) {
            $content = $this->getContentBasedOnType($request, 'settings', (object) [
                'type'    => $setting->type,
                'field'   => str_replace('.', '_', $setting->key),
                'group'   => $setting->group,
            ], $setting->details);

            if ($setting->type == 'image' && $content == null) {
                continue;
            }

            if ($setting->type == 'file' && $content == null) {
                continue;
            }

            // For multiple_images type, merge new images with existing ones
            if ($setting->type == 'multiple_images') {
                if ($content == null) {
                    // If no new images uploaded, keep existing ones
                    continue;
                } else {
                    // Merge new images with existing ones
                    $existingImages = json_decode($setting->value, true) ?? [];
                    $newImages = json_decode($content, true) ?? [];
                    $content = json_encode(array_merge($existingImages, $newImages));
                }
            }

            $key = preg_replace('/^'.Str::slug($setting->group).'./i', '', $setting->key);

            $setting->group = $request->input(str_replace('.', '_', $setting->key).'_group');
            $setting->key = implode('.', [Str::slug($setting->group), $key]);
            $setting->value = $content;
            $setting->save();
        }

        request()->flashOnly('setting_tab');

        return back()->with([
            'message'    => __('voyager::settings.successfully_saved'),
            'alert-type' => 'success',
        ]);
    }

    public function delete($id)
    {
        // Check permission
        $this->authorize('delete', Voyager::model('Setting'));

        $setting = Voyager::model('Setting')->find($id);

        Voyager::model('Setting')->destroy($id);

        request()->session()->flash('setting_tab', $setting->group);

        return back()->with([
            'message'    => __('voyager::settings.successfully_deleted'),
            'alert-type' => 'success',
        ]);
    }

    public function move_up($id)
    {
        // Check permission
        $this->authorize('edit', Voyager::model('Setting'));

        $setting = Voyager::model('Setting')->find($id);

        // Check permission
        $this->authorize('browse', $setting);

        $swapOrder = $setting->order;
        $previousSetting = Voyager::model('Setting')
                            ->where('order', '<', $swapOrder)
                            ->where('group', $setting->group)
                            ->orderBy('order', 'DESC')->first();
        $data = [
            'message'    => __('voyager::settings.already_at_top'),
            'alert-type' => 'error',
        ];

        if (isset($previousSetting->order)) {
            $setting->order = $previousSetting->order;
            $setting->save();
            $previousSetting->order = $swapOrder;
            $previousSetting->save();

            $data = [
                'message'    => __('voyager::settings.moved_order_up', ['name' => $setting->display_name]),
                'alert-type' => 'success',
            ];
        }

        request()->session()->flash('setting_tab', $setting->group);

        return back()->with($data);
    }

    public function delete_value($id)
    {
        $setting = Voyager::model('Setting')->find($id);

        // Check permission
        $this->authorize('delete', $setting);

        if (isset($setting->id)) {
            // If the type is an image... Then delete it
            if ($setting->type == 'image') {
                if (Storage::disk(config('voyager.storage.disk'))->exists($setting->value)) {
                    Storage::disk(config('voyager.storage.disk'))->delete($setting->value);
                }
            }
            $setting->value = '';
            $setting->save();
        }

        request()->session()->flash('setting_tab', $setting->group);

        return back()->with([
            'message'    => __('voyager::settings.successfully_removed', ['name' => $setting->display_name]),
            'alert-type' => 'success',
        ]);
    }

    public function delete_multiple_image($id, $imageIndex)
    {
        $setting = Voyager::model('Setting')->find($id);

        // Check permission
        $this->authorize('delete', $setting);

        if (isset($setting->id) && $setting->type == 'multiple_images') {
            $images = json_decode($setting->value, true) ?? [];
            
            if (isset($images[$imageIndex])) {
                // Delete the image file from storage
                if (Storage::disk(config('voyager.storage.disk'))->exists($images[$imageIndex])) {
                    Storage::disk(config('voyager.storage.disk'))->delete($images[$imageIndex]);
                }
                
                // Remove the image from the array
                array_splice($images, $imageIndex, 1);
                
                // Update the setting value
                $setting->value = json_encode($images);
                $setting->save();
            }
        }

        request()->session()->flash('setting_tab', $setting->group);

        return back()->with([
            'message'    => __('voyager::settings.successfully_removed', ['name' => __('voyager::generic.image')]),
            'alert-type' => 'success',
        ]);
    }

    public function move_down($id)
    {
        // Check permission
        $this->authorize('edit', Voyager::model('Setting'));

        $setting = Voyager::model('Setting')->find($id);

        // Check permission
        $this->authorize('browse', $setting);

        $swapOrder = $setting->order;

        $previousSetting = Voyager::model('Setting')
                            ->where('order', '>', $swapOrder)
                            ->where('group', $setting->group)
                            ->orderBy('order', 'ASC')->first();
        $data = [
            'message'    => __('voyager::settings.already_at_bottom'),
            'alert-type' => 'error',
        ];

        if (isset($previousSetting->order)) {
            $setting->order = $previousSetting->order;
            $setting->save();
            $previousSetting->order = $swapOrder;
            $previousSetting->save();

            $data = [
                'message'    => __('voyager::settings.moved_order_down', ['name' => $setting->display_name]),
                'alert-type' => 'success',
            ];
        }

        request()->session()->flash('setting_tab', $setting->group);

        return back()->with($data);
    }
}
