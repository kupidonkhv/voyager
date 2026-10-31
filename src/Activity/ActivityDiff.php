<?php

namespace TCG\Voyager\Activity;

use Illuminate\Database\Eloquent\Model;

class ActivityDiff
{
    public static function changedFields(Model $before, Model $after, array $excluded = []): array
    {
        return static::changedFieldsBetween($before->getAttributes(), $after->getAttributes(), $excluded);
    }

    public static function changedFieldsBetween(array $before, array $after, array $excluded = []): array
    {
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $changed = [];

        foreach ($keys as $key) {
            if (in_array($key, $excluded, true)) {
                continue;
            }

            if (!static::same($before[$key] ?? null, $after[$key] ?? null)) {
                $changed[] = (string) $key;
            }
        }

        sort($changed, SORT_STRING);

        return array_values(array_unique($changed));
    }

    public static function translationFields(
        Model $before,
        Model $after,
        string $relation = 'translations',
        array $excluded = []
    ): array {
        $before->loadMissing($relation);
        $after->loadMissing($relation);

        return static::changedFieldsBetween(
            static::translationMap($before, $relation, $excluded),
            static::translationMap($after, $relation, $excluded),
            $excluded
        );
    }

    protected static function translationMap(Model $model, string $relation, array $excluded): array
    {
        $map = [];

        foreach ($model->{$relation} as $translation) {
            $column = $translation->getAttribute('column');
            $locale = $translation->getAttribute('locale');

            if (!$column || !$locale || in_array($column, $excluded, true)) {
                continue;
            }

            $map[$column.'.'.$locale] = $translation->getAttribute('value');
        }

        return $map;
    }

    protected static function same($before, $after): bool
    {
        return static::normalize($before) === static::normalize($after);
    }

    protected static function normalize($value)
    {
        if (is_string($value)) {
            $trimmed = ltrim($value);

            if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $value = $decoded;
                }
            }
        }

        if (is_array($value)) {
            ksort($value);

            foreach ($value as $key => $item) {
                $value[$key] = static::normalize($item);
            }
        }

        return $value;
    }
}
