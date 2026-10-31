<?php

namespace TCG\Voyager\Activity;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Models\ActivityLog;
use TCG\Voyager\Models\DataType;
use TCG\Voyager\Models\Gallery;
use TCG\Voyager\Models\Menu;
use TCG\Voyager\Models\MenuItem;
use TCG\Voyager\Models\Setting;

class ActivitySubjectResolver
{
    public function label(ActivityLog $log, ?Authenticatable $user = null): ?string
    {
        if (!$log->subject_type || !$log->subject_id || $log->subject_type === 'media') {
            return null;
        }

        $model = $this->findSubject($log->subject_type, $log->subject_id);

        if (!$model) {
            return null;
        }

        if ($user && !$this->canBrowse($user, $model)) {
            return null;
        }

        foreach (['title', 'name', 'display_name_singular', 'key'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    protected function findSubject(string $type, string $id): ?Model
    {
        $class = $this->classFor($type);

        if (!$class || !class_exists($class) || !is_subclass_of($class, Model::class)) {
            return null;
        }

        return $class::query()->find($id);
    }

    protected function classFor(string $type): ?string
    {
        return match ($type) {
            'user' => Voyager::modelClass('User'),
            'role' => Voyager::modelClass('Role'),
            'setting' => Setting::class,
            'menu' => Menu::class,
            'menu_item' => MenuItem::class,
            'gallery' => Gallery::class,
            'bread' => DataType::class,
            default => $this->classFromDataType($type),
        };
    }

    protected function classFromDataType(string $type): ?string
    {
        $plural = Str::plural($type);
        $dataType = DataType::query()
            ->where('name', $plural)
            ->orWhere('slug', $plural)
            ->first();

        if (!$dataType || !$dataType->model_name) {
            return null;
        }

        return $dataType->model_name;
    }

    protected function canBrowse(Authenticatable $user, Model $model): bool
    {
        try {
            return Gate::forUser($user)->allows('browse', $model);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
