<?php

namespace TCG\Voyager\Activity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivitySubject
{
    protected const TABLE_ALIASES = [
        'data_types' => 'bread',
        'data_rows'  => 'bread',
        'menu_items' => 'menu_item',
        'user_roles' => 'user_role',
        'role_user'  => 'user_role',
    ];

    public static function forModel($subject): ?string
    {
        if ($subject instanceof Model) {
            return static::forTable($subject->getTable());
        }

        if (is_string($subject) && $subject !== '') {
            if (class_exists($subject) && is_subclass_of($subject, Model::class)) {
                return static::forTable((new $subject())->getTable());
            }

            return static::forTable($subject);
        }

        return null;
    }

    public static function forTable(?string $table): ?string
    {
        $table = trim((string) $table);

        if ($table === '') {
            return null;
        }

        if (isset(static::TABLE_ALIASES[$table])) {
            return static::TABLE_ALIASES[$table];
        }

        return Str::snake(Str::singular($table));
    }

    public static function resourceFromRoute(?string $routeName): ?string
    {
        if (!$routeName || !Str::startsWith($routeName, 'voyager.')) {
            return null;
        }

        $parts = explode('.', $routeName);

        return $parts[1] ?? null;
    }
}
