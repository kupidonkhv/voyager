<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TCG\Voyager\Activity\ActivityDiff;
use TCG\Voyager\Activity\ActivityRecorder;
use TCG\Voyager\Activity\ActivitySubject;
use TCG\Voyager\Events\FileDeleted;
use TCG\Voyager\Http\Controllers\ContentTypes\Checkbox;
use TCG\Voyager\Http\Controllers\ContentTypes\Coordinates;
use TCG\Voyager\Http\Controllers\ContentTypes\File;
use TCG\Voyager\Http\Controllers\ContentTypes\Image as ContentImage;
use TCG\Voyager\Http\Controllers\ContentTypes\MultipleCheckbox;
use TCG\Voyager\Http\Controllers\ContentTypes\MultipleImage;
use TCG\Voyager\Http\Controllers\ContentTypes\Password;
use TCG\Voyager\Http\Controllers\ContentTypes\Relationship;
use TCG\Voyager\Http\Controllers\ContentTypes\SelectMultiple;
use TCG\Voyager\Http\Controllers\ContentTypes\Text;
use TCG\Voyager\Http\Controllers\ContentTypes\Timestamp;
use TCG\Voyager\Traits\AlertsMessages;
use Validator;

abstract class Controller extends BaseController
{
    use DispatchesJobs;
    use ValidatesRequests;
    use AuthorizesRequests;
    use AlertsMessages;

    public function getSlug(Request $request)
    {
        if (isset($this->slug)) {
            $slug = $this->slug;
        } else {
            $slug = explode('.', $request->route()->getName())[1];
        }

        return $slug;
    }

    protected function activity(): ActivityRecorder
    {
        return app(ActivityRecorder::class);
    }

    protected function activityExcludedFields(): array
    {
        return (array) config('voyager-activity.excluded_fields', []);
    }

    protected function activityEventName($dataType, string $action): string
    {
        return match ($dataType->slug) {
            'users' => 'user.'.$action,
            'roles' => 'role.'.$action,
            'settings' => 'setting.'.$action,
            'menus' => 'menu.'.$action,
            'menu_items' => 'menu.item_'.$action,
            'data_types' => 'bread.'.$action,
            'galleries' => 'gallery.'.$action,
            default => 'record.'.$action,
        };
    }

    protected function activitySubjectType($dataType, $subject = null): ?string
    {
        return match ($dataType->slug) {
            'users' => 'user',
            'roles' => 'role',
            'settings' => 'setting',
            'menus' => 'menu',
            'menu_items' => 'menu_item',
            'data_types' => 'bread',
            'galleries' => 'gallery',
            default => $subject instanceof Model ? ActivitySubject::forModel($subject) : ActivitySubject::forTable($dataType->name),
        };
    }

    protected function activityRecordCreated($dataType, Model $subject, array $metadata = []): void
    {
        $this->activity()->recordEventSafe(
            $this->activityEventName($dataType, 'created'),
            'success',
            $subject,
            $dataType->slug,
            [],
            $metadata,
            $this->activitySubjectType($dataType, $subject),
            (string) $subject->getKey()
        );
    }

    protected function activityRecordUpdated($dataType, Model $before, Model $after, array $metadata = []): bool
    {
        $excluded = $this->activityExcludedFields();
        $isUser = $dataType->slug === 'users';

        if ($isUser) {
            $excluded[] = 'password';
        }

        $changed = ActivityDiff::changedFields($before, $after, $excluded);
        $translated = method_exists($before, 'translations') && method_exists($after, 'translations')
            ? ActivityDiff::translationFields($before, $after, 'translations', $excluded)
            : [];
        $changed = array_values(array_unique(array_merge($changed, $translated)));
        sort($changed, SORT_STRING);

        if ($changed) {
            $this->activity()->recordEventSafe(
                $this->activityEventName($dataType, 'updated'),
                'success',
                $after,
                $dataType->slug,
                $changed,
                $metadata,
                $this->activitySubjectType($dataType, $after),
                (string) $after->getKey()
            );
        }

        if ($isUser && $this->activityPasswordChanged($before, $after)) {
            $this->activity()->recordEventSafe(
                'user.password_changed',
                'success',
                $after,
                $dataType->slug,
                ['password'],
                [],
                'user',
                (string) $after->getKey()
            );
        }

        return $changed !== [];
    }

    protected function activityRecordDeleted($dataType, Model $subject, string $action = 'deleted'): void
    {
        $this->activity()->recordEventSafe(
            $this->activityEventName($dataType, $action),
            'success',
            $subject,
            $dataType->slug,
            [],
            [],
            $this->activitySubjectType($dataType, $subject),
            (string) $subject->getKey()
        );
    }

    protected function activityPasswordChanged(Model $before, Model $after): bool
    {
        return (string) ($before->getAttributes()['password'] ?? '') !== (string) ($after->getAttributes()['password'] ?? '');
    }

    protected function activityRelationSnapshot(Model $subject, $rows): array
    {
        if (!$subject->exists) {
            return [];
        }

        $snapshot = [];

        foreach ($rows as $row) {
            if ($row->type !== 'relationship' || ($row->details->type ?? null) !== 'belongsToMany') {
                continue;
            }

            $details = $row->details;
            $foreign = $details->foreign_pivot_key ?? Str::snake(class_basename($subject)).'_id';
            $related = $details->related_pivot_key ?? Str::snake(class_basename($details->model)).'_id';
            $parentKey = $details->parent_key ?? $subject->getKeyName();
            $relatedKey = $details->key ?? 'id';

            $snapshot[$row->field] = DB::table($details->pivot_table)
                ->where($foreign, $subject->getAttribute($parentKey))
                ->pluck($related)
                ->map(fn ($id) => (string) $id)
                ->all();
        }

        return $snapshot;
    }

    protected function activityRecordRelationChanges($dataType, Model $subject, array $before, array $after): void
    {
        $rows = $dataType->rows->keyBy('field');

        foreach ($before + $after as $field => $ids) {
            $old = $before[$field] ?? [];
            $new = $after[$field] ?? [];
            $row = $rows->get($field);

            if (!$row || ($row->details->type ?? null) !== 'belongsToMany') {
                continue;
            }

            $relatedType = ActivitySubject::forModel($row->details->model ?? null)
                ?: ActivitySubject::forTable($row->details->table ?? null);

            foreach (array_diff($new, $old) as $id) {
                $this->activityRecordRelationEvent($dataType, $subject, 'attached', $field, $relatedType, (string) $id);
            }

            foreach (array_diff($old, $new) as $id) {
                $this->activityRecordRelationEvent($dataType, $subject, 'detached', $field, $relatedType, (string) $id);
            }
        }
    }

    protected function activityRecordRelationEvent($dataType, Model $subject, string $action, string $field, ?string $relatedType, string $relatedId): void
    {
        $isUserRole = $dataType->slug === 'users' && $relatedType === 'role';

        if ($isUserRole) {
            $this->activity()->recordEventSafe(
                'user.role_'.$action,
                'success',
                $subject,
                $dataType->slug,
                [],
                ['role_id' => $relatedId],
                'user',
                (string) $subject->getKey()
            );

            return;
        }

        $this->activity()->recordEventSafe(
            'relation.'.$action,
            'success',
            $subject,
            $dataType->slug,
            [],
            [
                'relation'     => $field,
                'related_type' => $relatedType,
                'related_id'   => $relatedId,
            ],
            $this->activitySubjectType($dataType, $subject),
            (string) $subject->getKey()
        );
    }

    protected function activityRecordMedia(string $event, ?string $subjectId, ?string $resource, array $metadata, string $result = 'success'): void
    {
        $this->activity()->recordEventSafe(
            $event,
            $result,
            null,
            $resource,
            [],
            $metadata,
            'media',
            $subjectId
        );
    }

    protected function queryBuilderAttributes(Request $request, string $slug, $dataType, $rows, $existing = null): array
    {
        $attributes = [];

        foreach ($rows as $row) {
            if (in_array($row->field, ['id', 'created_at', 'updated_at'], true) || $row->type === 'relationship') {
                continue;
            }

            if (!$request->hasFile($row->field) && !$request->has($row->field) && $row->type !== 'checkbox') {
                continue;
            }

            $content = $this->getContentBasedOnType($request, $slug, $row, $row->details);

            if (is_null($content)) {
                if ($existing && isset($existing->{$row->field})) {
                    $content = $existing->{$row->field};
                } elseif ($row->type === 'checkbox') {
                    $content = 0;
                } else {
                    continue;
                }
            }

            $attributes[$row->field] = $content;
        }

        if ($existing === null && DB::getSchemaBuilder()->hasColumn($dataType->name, 'created_at')) {
            $attributes['created_at'] = now();
        }

        if (DB::getSchemaBuilder()->hasColumn($dataType->name, 'updated_at')) {
            $attributes['updated_at'] = now();
        }

        return $attributes;
    }

    public function insertUpdateData($request, $slug, $rows, $data)
    {
        $multi_select = [];

        // Pass $rows so that we avoid checking unused fields
        $request->attributes->add(['breadRows' => $rows->pluck('field')->toArray()]);

        /*
         * Prepare Translations and Transform data
         */
        $translations = is_bread_translatable($data)
                        ? $data->prepareTranslations($request)
                        : [];

        foreach ($rows as $row) {
            // if the field for this row is absent from the request, continue
            // checkboxes will be absent when unchecked, thus they are the exception
            if (!$request->hasFile($row->field) && !$request->has($row->field) && $row->type !== 'checkbox') {
                // if the field is a belongsToMany relationship, don't remove it
                // if no content is provided, that means the relationships need to be removed
                if (isset($row->details->type) && $row->details->type !== 'belongsToMany') {
                    continue;
                }
            }

            // Value is saved from $row->details->column row
            if ($row->type == 'relationship' && $row->details->type == 'belongsTo') {
                continue;
            }

            $content = $this->getContentBasedOnType($request, $slug, $row, $row->details);

            if ($row->type == 'relationship' && $row->details->type != 'belongsToMany') {
                $row->field = @$row->details->column;
            }

            /*
             * merge ex_images/files and upload images/files
             */
            if (in_array($row->type, ['multiple_images', 'file']) && !is_null($content)) {
                if (isset($data->{$row->field})) {
                    $ex_files = json_decode($data->{$row->field}, true);
                    if (!is_null($ex_files)) {
                        $content = json_encode(array_merge($ex_files, json_decode($content)));
                    }
                }
            }

            if (is_null($content)) {

                // If the image upload is null and it has a current image keep the current image
                if ($row->type == 'image' && is_null($request->input($row->field)) && isset($data->{$row->field})) {
                    $content = $data->{$row->field};
                }

                // If the multiple_images upload is null and it has a current image keep the current image
                if ($row->type == 'multiple_images' && is_null($request->input($row->field)) && isset($data->{$row->field})) {
                    $content = $data->{$row->field};
                }

                // If the file upload is null and it has a current file keep the current file
                if ($row->type == 'file') {
                    $content = $data->{$row->field};
                    if (!$content) {
                        $content = json_encode([]);
                    }
                }

                if ($row->type == 'password') {
                    $content = $data->{$row->field};
                }
            }

            if ($row->type == 'relationship' && $row->details->type == 'belongsToMany') {
                // Only if select_multiple is working with a relationship
                $multi_select[] = [
                    'model'           => $row->details->model,
                    'content'         => $content,
                    'table'           => $row->details->pivot_table,
                    'foreignPivotKey' => $row->details->foreign_pivot_key ?? null,
                    'relatedPivotKey' => $row->details->related_pivot_key ?? null,
                    'parentKey'       => $row->details->parent_key ?? null,
                    'relatedKey'      => $row->details->key,
                ];
            } else {
                $data->{$row->field} = $content;
            }
        }

        if (isset($data->additional_attributes)) {
            foreach ($data->additional_attributes as $attr) {
                if ($request->has($attr)) {
                    $data->{$attr} = $request->{$attr};
                }
            }
        }

        $data->save();

        // Save translations
        if (count($translations) > 0) {
            $data->saveTranslations($translations);
        }

        foreach ($multi_select as $sync_data) {
            $data->belongsToMany(
                $sync_data['model'],
                $sync_data['table'],
                $sync_data['foreignPivotKey'],
                $sync_data['relatedPivotKey'],
                $sync_data['parentKey'],
                $sync_data['relatedKey']
            )->sync($sync_data['content']);
        }

        // Rename folders for newly created data through media-picker
        if ($request->session()->has($slug.'_path') || $request->session()->has($slug.'_uuid')) {
            $old_path = $request->session()->get($slug.'_path');
            $uuid = $request->session()->get($slug.'_uuid');
            $new_path = str_replace($uuid, $data->getKey(), $old_path);
            $folder_path = substr($old_path, 0, strpos($old_path, $uuid)).$uuid;

            $rows->where('type', 'media_picker')->each(function ($row) use ($data, $uuid) {
                $data->{$row->field} = str_replace($uuid, $data->getKey(), $data->{$row->field});
            });
            $data->save();
            if ($old_path != $new_path && 
                !Storage::disk(config('voyager.storage.disk'))->exists($new_path) && 
                Storage::disk(config('voyager.storage.disk'))->exists($old_path)
                ) 
            {
                $request->session()->forget([$slug.'_path', $slug.'_uuid']);
                Storage::disk(config('voyager.storage.disk'))->move($old_path, $new_path);
                Storage::disk(config('voyager.storage.disk'))->deleteDirectory($folder_path);
            }
        }

        return $data;
    }

    /**
     * Validates bread POST request.
     *
     * @param array  $data The data
     * @param array  $rows The rows
     * @param string $slug Slug
     * @param int    $id   Id of the record to update
     *
     * @return mixed
     */
    public function validateBread($data, $rows, $name = null, $id = null)
    {
        $rules = [];
        $messages = [];
        $customAttributes = [];
        $is_update = $name && $id;

        $fieldsWithValidationRules = $this->getFieldsWithValidationRules($rows);

        foreach ($fieldsWithValidationRules as $field) {
            $fieldRules = $field->details->validation->rule;
            $fieldName = $field->field;

            // Show the field's display name on the error message
            if (!empty($field->display_name)) {
                if (!empty($data[$fieldName]) && is_array($data[$fieldName])) {
                    foreach ($data[$fieldName] as $index => $element) {
                        if ($element instanceof UploadedFile) {
                            $name = $element->getClientOriginalName();
                        } else {
                            $name = $index + 1;
                        }

                        $customAttributes[$fieldName.'.'.$index] = $field->getTranslatedAttribute('display_name').' '.$name;
                    }
                } else {
                    $customAttributes[$fieldName] = $field->getTranslatedAttribute('display_name');
                }
            }

            // If field is an array apply rules to all array elements
            $fieldName = !empty($data[$fieldName]) && is_array($data[$fieldName]) ? $fieldName.'.*' : $fieldName;

            // Get the rules for the current field whatever the format it is in
            $rules[$fieldName] = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);

            if ($id && property_exists($field->details->validation, 'edit')) {
                $action_rules = $field->details->validation->edit->rule;
                $rules[$fieldName] = array_merge($rules[$fieldName], (is_array($action_rules) ? $action_rules : explode('|', $action_rules)));
            } elseif (!$id && property_exists($field->details->validation, 'add')) {
                $action_rules = $field->details->validation->add->rule;
                $rules[$fieldName] = array_merge($rules[$fieldName], (is_array($action_rules) ? $action_rules : explode('|', $action_rules)));
            }
            // Fix Unique validation rule on Edit Mode
            if ($is_update) {
                foreach ($rules[$fieldName] as &$fieldRule) {
                    if (strpos(strtoupper($fieldRule), 'UNIQUE') !== false) {
                        $fieldRule = \Illuminate\Validation\Rule::unique($name)->ignore($id);
                    }
                }
            }

            // Set custom validation messages if any
            if (!empty($field->details->validation->messages)) {
                foreach ($field->details->validation->messages as $key => $msg) {
                    $messages["{$field->field}.{$key}"] = $msg;
                }
            }
        }

        return Validator::make($data, $rules, $messages, $customAttributes);
    }

    public function getContentBasedOnType(Request $request, $slug, $row, $options = null)
    {
        switch ($row->type) {
            /********** PASSWORD TYPE **********/
            case 'password':
                return (new Password($request, $slug, $row, $options))->handle();
            /********** CHECKBOX TYPE **********/
            case 'checkbox':
                return (new Checkbox($request, $slug, $row, $options))->handle();
            /********** MULTIPLE CHECKBOX TYPE **********/
            case 'multiple_checkbox':
                return (new MultipleCheckbox($request, $slug, $row, $options))->handle();
            /********** FILE TYPE **********/
            case 'file':
                return (new File($request, $slug, $row, $options))->handle();
            /********** MULTIPLE IMAGES TYPE **********/
            case 'multiple_images':
                return (new MultipleImage($request, $slug, $row, $options))->handle();
            /********** SELECT MULTIPLE TYPE **********/
            case 'select_multiple':
                return (new SelectMultiple($request, $slug, $row, $options))->handle();
            /********** IMAGE TYPE **********/
            case 'image':
                return (new ContentImage($request, $slug, $row, $options))->handle();
            /********** DATE TYPE **********/
            case 'date':
            /********** TIMESTAMP TYPE **********/
            case 'timestamp':
                return (new Timestamp($request, $slug, $row, $options))->handle();
            /********** COORDINATES TYPE **********/
            case 'coordinates':
                return (new Coordinates($request, $slug, $row, $options))->handle();
            /********** RELATIONSHIPS TYPE **********/
            case 'relationship':
                return (new Relationship($request, $slug, $row, $options))->handle();
            /********** ALL OTHER TEXT TYPE **********/
            default:
                return (new Text($request, $slug, $row, $options))->handle();
        }
    }

    public function deleteFileIfExists($path)
    {
        if ($path && Storage::disk(config('voyager.storage.disk'))->exists($path)) {
            Storage::disk(config('voyager.storage.disk'))->delete($path);
            event(new FileDeleted($path));
        }
    }

    /**
     * Get fields having validation rules in proper format.
     *
     * @param array $fieldsConfig
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getFieldsWithValidationRules($fieldsConfig)
    {
        return $fieldsConfig->filter(function ($value) {
            if (empty($value->details)) {
                return false;
            }

            return !empty($value->details->validation->rule);
        });
    }
}
