<?php

namespace MonstreX\VoyagerExtension\Controllers;

use Exception;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use TCG\Voyager\Activity\ActivityRecorder;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Support\SafeUpload;
use Spatie\MediaLibrary\MediaCollections\Models\Media;


class VoyagerExtensionController extends BaseController
{

    protected function activity(): ActivityRecorder
    {
        return app(ActivityRecorder::class);
    }

    private function routeSlug(Request $request): ?string
    {
        $slug = $request->route('dataTypeSlug') ?? $request->input('slug');

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    private function authorizeDataType(Request $request, string $ability, ?string $slug = null)
    {
        $slug = $slug ?? $this->routeSlug($request);

        if (!$slug) {
            abort(403);
        }

        $dataType = Voyager::model('DataType')->where('slug', $slug)->first();

        if (!$dataType) {
            abort(404);
        }

        $user = $request->user();
        $permission = $ability.'_'.($dataType->name ?: $dataType->slug);

        if (!$user || !method_exists($user, 'hasPermission') || !$user->hasPermission($permission)) {
            abort(403);
        }

        return $dataType;
    }

    private function assertMediaAccess(Request $request, $dataType, Media $media, string $ability): void
    {
        $modelClass = $dataType->model_name;
        $morphClass = (new $modelClass)->getMorphClass();

        if ($media->model_type !== $morphClass) {
            abort(403);
        }

        $parent = $media->model;

        if (!$parent) {
            abort(403);
        }

        $policy = Gate::getPolicyFor($parent);

        if ($policy && !Gate::forUser($request->user())->allows($ability, $parent)) {
            abort(403);
        }
    }

    /*
     * Load Translations
     */
    public function load_translations(Request $request)
    {
        $bread = Lang::get('voyager-extension::bread');

        return response()->json([
            'bread' => is_array($bread) ? $bread : [],
        ]);
    }

    /*
     * Get Data Type Row Instance and Field Data
     */
    private function getDataType($slug, $id, $field)
    {
        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (!$dataType) {
            abort(404);
        }

        // Get field meta data and options
        $dataRow = $dataType->editRows->filter(function ($item) use ($field) {
            return $item->field == $field;
        })->first();

        // Load model and find record
        $model = app($dataType->model_name);
        $data = $model->findOrFail($id);

        // Check if field exists
        if (!isset($data->{$field}) && $data->{$field} !== null) {
            throw new Exception(__('voyager::generic.field_does_not_exist'), 400);
        }

        return [
            'dataType' => $dataType,
            'dataRow' => $dataRow,
            'data' => $data,
        ];
    }

    /*
     * Load AJAX Content (HTML rendered) using Request params
     */
    public function load_image_form(Request $request)
    {
        $dataType = $this->authorizeDataType($request, 'read');
        $field = $request->get('field');
        $id = $request->get('id');
        $media_file_id = $request->get('media_file_id');

        $row = $this->getDataType($dataType->slug, $id, $field);

        $file = $row['data']->getMedia($field)->where('id', $media_file_id)->first();

        if ($file) {
            $this->assertMediaAccess($request, $dataType, $file, 'read');
        }

        return view('voyager-extension::forms.form-ajax', [
            'dataRow' => $row['dataRow'],
            'data' => $row['data'],
            'file' => $file,
            'model' => [
                'model' => $row['dataType']->model_name,
                'id' => $id,
                'field' => $field,
                'media_file_id' => $media_file_id,
            ]
        ]);
    }

    /*
     *  Get Group Form
     */
    public function load_group_form(Request $request)
    {
        $dataType = $this->authorizeDataType($request, 'read');

        try {
            $slug = $dataType->slug;
            $id = $request->get('id');
            $field = $request->get('field');

            $row = $this->getDataType($slug, $id, $field);

            $group = json_decode($row['data']->{$field});
            if (!isset($group->fields)) {
                $fieldValue = $row['dataRow']->details->fields;
            } else {
                $fieldValue = $group->fields;
            }

            return view('voyager-extension::forms.form-group-ajax', [
                'slug' => $slug,
                'id' => $id,
                'field' => $field,
                'fields' => $fieldValue,
            ]);

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /*
     *  Update media file
     */
    public function update_media(Request $request)
    {
        $dataType = $this->authorizeDataType($request, 'edit');
        $id = $request->get('id');
        $field = $request->get('field');
        $media_file_id = $request->get('media_file_id');

        $dataRow = $dataType->editRows->firstWhere('field', $field);
        if (!$dataRow) {
            abort(403);
        }

        $data = app($dataType->model_name)->findOrFail($id);
        $file = $data->getMedia($field)->where('id', (int) $media_file_id)->first();

        if (!$file) {
            abort(404);
        }

        $this->assertMediaAccess($request, $dataType, $file, 'edit');

        $allowedFields = ['title', 'alt', 'description', 'link', 'order'];
        $extraFields = data_get($dataRow, 'details.extra_fields', []);
        if (is_array($extraFields) || is_object($extraFields)) {
            $allowedFields = array_merge($allowedFields, array_keys((array) $extraFields));
        }

        $customFields = array_intersect_key(
            $request->except(['model', 'id', 'field', 'media_file_id', '_token']),
            array_flip($allowedFields)
        );

        try {
            foreach ($customFields as $key => $value) {
                $file->setCustomProperty($key, $value);
            }
            $file->save();
        } catch (Exception $error) {
            return json_response_with_error(500, $error);
        }

        return json_response_with_success(200, __('voyager-extension::bread.media_updated'));
    }

    /*
     *  Change media file
     */
    public function change_media(Request $request)
    {
        $dataType = $this->authorizeDataType($request, 'edit');
        $id = $request->get('id');
        $field = $request->get('field');
        $media_file_id = $request->get('media_file_id');

        $dataRow = $dataType->editRows->firstWhere('field', $field);
        if (!$dataRow) {
            abort(403);
        }

        $uploaded = $request->file($field);
        if (!$uploaded) {
            abort(400);
        }

        try {
            SafeUpload::assertUploadedFile($uploaded);
            $extension = SafeUpload::normalizeExtension($uploaded->getClientOriginalExtension());
            SafeUpload::assertImageExtension($extension);
            $newFileName = $this->getFileName($uploaded);
        } catch (\InvalidArgumentException $error) {
            return json_response_with_error(400, $error);
        }

        $data = app($dataType->model_name)->findOrFail($id);
        $old_file = $data->getMedia($field)->where('id', (int) $media_file_id)->first();

        if (!$old_file) {
            abort(404);
        }

        $this->assertMediaAccess($request, $dataType, $old_file, 'edit');

        try {
            $old_properties = [];
            $old_properties['title'] = $old_file->getCustomProperty('title');
            $old_properties['alt'] = $old_file->getCustomProperty('alt');

            if (isset($dataRow->details->extra_fields)) {
                foreach ($dataRow->details->extra_fields as $key => $item) {
                    $old_properties[$key] = $old_file->getCustomProperty($key);
                }
            }

            $new_file = $data->addMediaFromRequest($field)
                ->withCustomProperties($old_properties)
                ->setFileName($newFileName)
                ->toMediaCollection($field);

            $all_files = $data->getMedia($field);
            $new_order = [];
            foreach ($all_files as $key => $item) {
                if ($item->id === (int) $media_file_id) {
                    $new_order[] = $new_file->id;
                } else {
                    $new_order[] = $item->id;
                }
            }

            Media::setNewOrder($new_order);

            $old_file->delete();

            $this->activity()->recordEventSafe(
                'media.replaced',
                'success',
                null,
                $dataType->slug,
                [],
                [
                    'disk' => $new_file->disk,
                    'path' => $new_file->getPathRelativeToRoot(),
                    'size' => $new_file->size,
                ],
                'media',
                (string) $new_file->getKey()
            );

            $file_name_size = Str::limit($new_file->file_name, 20, ' (...)');
            $file_name_size .= ' <i class="' . ($new_file->size > 100000 ? 'large' : '') . '">' . $new_file->human_readable_size . '</i>';
        } catch (Exception $error) {
            return json_response_with_error(500, $error);
        }

        return json_response_with_success(
            200,
            __('voyager-extension::bread.media_updated'), [
            'file_url' => $new_file->getFullUrl(),
            'file_name' => $new_file->file_name,
            'file_name_size' => $file_name_size,
            'file_id' => $new_file->id,
        ]);
    }

    /*
     *  Remove media file
     */
    public function remove_media(Request $request)
    {
        $dataType = $this->authorizeDataType($request, 'delete');
        $mediaIds = $request->get('media_ids');
        if (!$mediaIds) {
            $mediaIds = [$request->get('media_file_id')];
        }
        if (!is_array($mediaIds)) {
            $mediaIds = [$mediaIds];
        }
        $mediaIds = array_values(array_unique(array_filter(array_map('intval', $mediaIds))));
        if (!$mediaIds) {
            abort(400);
        }
        $media = Media::whereIn('id', $mediaIds)->get();
        if ($media->count() !== count($mediaIds)) {
            abort(404);
        }
        foreach ($media as $mediaItem) {
            $this->assertMediaAccess($request, $dataType, $mediaItem, 'delete');
        }
        try {
            foreach ($media as $mediaItem) {
                $mediaItem->delete();
            }
        } catch (Exception $error) {
            return json_response_with_error(500, $error);
        }
        foreach ($media as $mediaItem) {
            $this->activity()->recordEventSafe(
                'media.deleted',
                'success',
                null,
                'media',
                [],
                [
                    'disk' => $mediaItem->disk,
                    'path' => $mediaItem->getPathRelativeToRoot(),
                    'size' => $mediaItem->size,
                ],
                'media',
                (string) $mediaItem->getKey()
            );
        }
        return json_response_with_success(200, __('voyager-extension::bread.media_removed'));
    }

    /*
     * Sort Media files
     */
    public function sort_media(Request $request)
    {
        $dataType = $this->authorizeDataType($request, 'edit');
        $files_ids_order = $request->get('files_ids_order');
        if (!is_array($files_ids_order) || !$files_ids_order) {
            abort(400);
        }
        $files_ids_order = array_values(array_unique(array_map('intval', $files_ids_order)));
        $media = Media::whereIn('id', $files_ids_order)->get();
        if ($media->count() !== count($files_ids_order)) {
            abort(404);
        }
        foreach ($media as $mediaItem) {
            $this->assertMediaAccess($request, $dataType, $mediaItem, 'edit');
        }
        try {
            Media::setNewOrder($files_ids_order);
            return json_response_with_success(200, __('voyager-extension::bread.media_sorted'));
        } catch (Exception $error) {
            return json_response_with_error(500, $error);
        }
    }

    /*
     * Return content of the requested asset file (js, css and etc)
     *
     * This function used as is from the original Voyager 1.3.1
     * input: 'http://site.com/admin/voyager-assets?path=js%2Fapp.js'
     */
    public function assets(Request $request)
    {
        $requested = urldecode((string) $request->path);

        try {
            if (class_exists(\League\Flysystem\Util::class)) {
                $requested = \League\Flysystem\Util::normalizeRelativePath($requested);
            } elseif (class_exists(\League\Flysystem\WhitespacePathNormalizer::class)) {
                $normalizer = new \League\Flysystem\WhitespacePathNormalizer();
                $requested = $normalizer->normalizePath($requested);
            }
        } catch (\LogicException $e) {
            abort(404);
        }

        return $this->assets_file($this->resolveAssetPath($requested));
    }

    /*
     * Return content of the requested asset file (js, css and etc)
     *
     * input: 'http://site.com/admin/voyager-extension/path/to/assets/file.*'
     */
    public function assets_regular(Request $request)
    {
        $alias = $request->route('alias');

        return $this->assets_file($this->resolveAssetPath(urldecode((string) $alias)));
    }

    private function resolveAssetPath(string $relative): string
    {
        $relative = str_replace(["\0", '\\'], ['', '/'], $relative);
        $relative = ltrim($relative, '/');

        if ($relative === '') {
            abort(404);
        }

        foreach (explode('/', $relative) as $segment) {
            if ($segment === '..' || $segment === '.') {
                abort(404);
            }
        }

        $root = realpath(dirname(__DIR__, 3).'/publishable/assets/vendor/voyager-extension');

        if ($root === false) {
            abort(404);
        }

        $path = realpath($root.DIRECTORY_SEPARATOR.$relative);
        $prefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if ($path === false || !is_file($path) || !str_starts_with($path, $prefix)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, SafeUpload::blockedExtensions(), true)
            || in_array($extension, ['htaccess', 'htpasswd', 'env'], true)) {
            abort(404);
        }

        return $path;
    }

    private function assets_file($path)
    {
        if (File::exists($path)) {
            $mime = '';
            if (Str::endsWith($path, '.js')) {
                $mime = 'text/javascript';
            } elseif (Str::endsWith($path, '.css')) {
                $mime = 'text/css';
            } else {
                $mime = File::mimeType($path);
            }
            $response = response(File::get($path), 200, ['Content-Type' => $mime]);
            $response->setSharedMaxAge(31536000);
            $response->setMaxAge(31536000);
            $response->setExpires(new \DateTime('+1 year'));

            return $response;
        }
        abort(404);
    }

    private function getFileName($file)
    {
        $extension = SafeUpload::normalizeExtension($file->getClientOriginalExtension());
        SafeUpload::assertImageExtension($extension);

        $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        if (config('voyager-extension.slug_filenames')) {
            $filename = Str::slug($filename);
        }

        return SafeUpload::sanitizeBaseName($filename, $extension);
    }
}
