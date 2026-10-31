<?php

namespace TCG\Voyager\Activity;

use Illuminate\Support\Str;
use InvalidArgumentException;

class ActivitySanitizer
{
    protected const CATEGORIES = ['content', 'media', 'access', 'configuration', 'auth'];
    protected const RESULTS = ['success', 'failure', 'denied', 'partial'];
    protected const SOURCES = ['admin', 'api', 'console', 'job'];

    protected const MAX_FIELD_LENGTH = 191;
    protected const MAX_PATH_LENGTH = 1024;
    protected const MAX_METADATA_STRING = 1024;

    protected static array $metadataSchema = [
        'record.created'              => [],
        'record.updated'              => [],
        'record.deleted'              => [],
        'record.force_deleted'        => [],
        'record.restored'             => [],
        'record.cloned'               => ['source_id'],
        'relation.attached'           => ['relation', 'related_type', 'related_id'],
        'relation.detached'           => ['relation', 'related_type', 'related_id'],
        'relation.updated'            => ['relation', 'related_type', 'related_id', 'pivot_fields'],
        'records.reordered'           => ['count'],
        'media.uploaded'              => ['disk', 'path', 'size'],
        'media.deleted'               => ['disk', 'path', 'size'],
        'media.moved'                 => ['disk', 'path', 'size'],
        'media.renamed'               => ['disk', 'path', 'size'],
        'media.replaced'              => ['disk', 'path', 'size'],
        'media.directory_created'     => ['disk', 'path'],
        'media.directory_deleted'     => ['disk', 'path'],
        'media.directory_moved'       => ['disk', 'path'],
        'media.directory_renamed'     => ['disk', 'path'],
        'gallery.created'             => [],
        'gallery.updated'             => [],
        'gallery.deleted'             => [],
        'gallery.reordered'           => ['count'],
        'user.created'                => [],
        'user.updated'                => [],
        'user.deleted'                => [],
        'user.password_changed'       => [],
        'user.role_attached'          => ['role_id'],
        'user.role_detached'          => ['role_id'],
        'role.created'                => [],
        'role.updated'                => [],
        'role.deleted'                => [],
        'role.permission_attached'    => ['permission_id'],
        'role.permission_detached'    => ['permission_id'],
        'setting.created'             => ['key'],
        'setting.updated'             => ['key'],
        'setting.deleted'             => ['key'],
        'bread.created'               => ['slug'],
        'bread.updated'               => ['slug', 'relation'],
        'bread.deleted'               => ['slug'],
        'menu.created'                => [],
        'menu.updated'                => [],
        'menu.deleted'                => [],
        'menu.item_created'           => ['menu_id'],
        'menu.item_updated'           => ['menu_id'],
        'menu.item_deleted'           => ['menu_id'],
        'menu.reordered'              => ['count', 'menu_id'],
        'auth.login_succeeded'        => [],
        'auth.login_failed'           => ['reason_code'],
        'auth.logout'                 => [],
        'access.denied'               => [],
    ];

    protected const FORBIDDEN_METADATA_KEYS = [
        'old_values', 'new_values', 'before', 'after', 'snapshot', 'content',
        'password', 'password_hash', 'token', 'secret', 'value', 'body', 'html',
        'markdown', 'json', 'request', 'response', 'sql', 'bindings', 'model',
        'attributes', 'changes', 'diff', 'payload',
    ];

    public static function register(string $event, array $allowedMetadataKeys = []): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/', $event) || strlen($event) > 100) {
            throw new InvalidArgumentException('Invalid activity event code.');
        }

        foreach ($allowedMetadataKeys as $key) {
            if (!is_string($key) || !preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
                throw new InvalidArgumentException('Invalid activity metadata key.');
            }
        }

        static::$metadataSchema[$event] = array_values(array_unique($allowedMetadataKeys));
        ksort(static::$metadataSchema);
    }

    public static function eventCodes(): array
    {
        $events = array_keys(static::$metadataSchema);
        sort($events);

        return $events;
    }

    public static function categories(): array
    {
        return static::CATEGORIES;
    }

    public static function results(): array
    {
        return static::RESULTS;
    }

    public function sanitize(ActivityEvent $event): array
    {
        $this->validateEvent($event);

        $allowedKeys = static::$metadataSchema[$event->event];
        $truncated = false;
        $changedFields = $this->sanitizeChangedFields($event->changedFields, $truncated);
        $changedFieldsCount = count($changedFields);
        $metadata = $this->sanitizeMetadata($event->metadata, $allowedKeys, $truncated);

        [$changedFields, $metadata, $truncated] = $this->applySizeLimit(
            $changedFields,
            $metadata,
            $changedFieldsCount,
            $truncated
        );

        $changedJson = $changedFields ? $this->json($changedFields) : null;
        $metadataJson = $metadata ? $this->json($metadata) : null;

        $uuid = $event->eventUuid ?: (string) Str::uuid();
        if (!Str::isUuid($uuid)) {
            throw new InvalidArgumentException('Invalid activity event UUID.');
        }

        $operationId = $event->operationId;
        if ($operationId !== null && !Str::isUuid($operationId)) {
            throw new InvalidArgumentException('Invalid activity operation UUID.');
        }

        $requestId = $event->requestId;
        if ($requestId !== null && !Str::isUuid($requestId)) {
            throw new InvalidArgumentException('Invalid activity request UUID.');
        }

        $occurredAt = now('UTC')->format('Y-m-d H:i:s.u');

        return [
            'event_uuid'     => $uuid,
            'occurred_at'    => $occurredAt,
            'recorded_at'    => now('UTC')->format('Y-m-d H:i:s.u'),
            'category'       => $event->category,
            'event'          => $event->event,
            'result'         => $event->result,
            'actor_type'     => $this->nullableAlias($event->actorType),
            'actor_id'       => $this->nullableId($event->actorId),
            'subject_type'   => $this->nullableAlias($event->subjectType),
            'subject_id'     => $this->nullableId($event->subjectId),
            'resource'       => $this->nullableResource($event->resource),
            'changed_fields' => $changedJson,
            'metadata'       => $metadataJson,
            'request_id'     => $requestId,
            'operation_id'   => $operationId,
            'source'         => $this->source($event->source),
            'ip_address'     => $this->ip($event->ipAddress),
            'route_name'     => $this->routeName($event->routeName),
            'schema_version' => 1,
        ];
    }

    protected function validateEvent(ActivityEvent $event): void
    {
        if (!in_array($event->category, static::CATEGORIES, true)) {
            throw new InvalidArgumentException('Invalid activity category.');
        }

        if (!array_key_exists($event->event, static::$metadataSchema)) {
            throw new InvalidArgumentException('Unregistered activity event.');
        }

        if (!in_array($event->result, static::RESULTS, true)) {
            throw new InvalidArgumentException('Invalid activity result.');
        }
    }

    protected function sanitizeChangedFields(array $fields, bool &$truncated): array
    {
        $result = [];

        foreach ($fields as $field) {
            if (!is_string($field) && !is_int($field)) {
                throw new InvalidArgumentException('Changed fields must contain field names only.');
            }

            $field = trim((string) $field);
            if ($field === '') {
                continue;
            }

            if (strlen($field) > static::MAX_FIELD_LENGTH) {
                $field = substr($field, 0, static::MAX_FIELD_LENGTH);
                $truncated = true;
            }

            $result[] = $field;
        }

        $result = array_values(array_unique($result));
        sort($result, SORT_STRING);

        return $result;
    }

    protected function sanitizeMetadata(array $metadata, array $allowedKeys, bool &$truncated): array
    {
        $result = [];

        foreach ($metadata as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Activity metadata keys must be strings.');
            }

            if (in_array($key, static::FORBIDDEN_METADATA_KEYS, true)) {
                throw new InvalidArgumentException('Forbidden activity metadata key.');
            }

            if (!in_array($key, $allowedKeys, true)) {
                throw new InvalidArgumentException('Activity metadata key is not registered for this event.');
            }

            if (is_array($value)) {
                $value = array_values(array_filter($value, fn ($item) => is_scalar($item) || is_null($item)));
                $value = array_map(fn ($item) => is_string($item) ? $this->safeString($item, $truncated) : $item, $value);
            } elseif (is_string($value)) {
                $value = $this->safeString($value, $truncated);
            } elseif (!is_scalar($value) && !is_null($value)) {
                throw new InvalidArgumentException('Activity metadata values must be scalar.');
            }

            if ($key === 'path') {
                $value = $this->relativePath($value, $truncated);
            }

            $result[$key] = $value;
        }

        ksort($result);

        return $result;
    }

    protected function applySizeLimit(
        array $changedFields,
        array $metadata,
        int $changedFieldsCount,
        bool $truncated
    ): array {
        $max = max(256, (int) config('voyager-activity.max_details_bytes', 4096));

        while ($this->detailsSize($changedFields, $metadata) > $max && $changedFields) {
            array_pop($changedFields);
            $truncated = true;
        }

        if ($this->detailsSize($changedFields, $metadata) > $max) {
            $keys = array_keys($metadata);
            rsort($keys, SORT_STRING);

            foreach ($keys as $key) {
                unset($metadata[$key]);
                $truncated = true;

                if ($this->detailsSize($changedFields, $metadata) <= $max) {
                    break;
                }
            }
        }

        if ($truncated) {
            $metadata['details_truncated'] = true;
            $metadata['changed_fields_count'] = $changedFieldsCount;
        }

        if ($this->detailsSize($changedFields, $metadata) > $max) {
            $changedFields = [];
            $metadata = [
                'details_truncated'     => true,
                'changed_fields_count'  => $changedFieldsCount,
            ];
        }

        ksort($metadata);

        return [$changedFields, $metadata, $truncated];
    }

    protected function detailsSize(array $changedFields, array $metadata): int
    {
        $size = 0;

        if ($changedFields) {
            $size += strlen($this->json($changedFields));
        }

        if ($metadata) {
            $size += strlen($this->json($metadata));
        }

        return $size;
    }

    protected function relativePath($value, bool &$truncated): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace('\\', '/', (string) $value);
        $value = preg_replace('#/+#', '/', $value);

        if ($value === '' || str_starts_with($value, '/') || str_contains($value, '://') || str_contains($value, '?') || str_contains($value, '..')) {
            throw new InvalidArgumentException('Activity path must be a safe relative path.');
        }

        $value = ltrim($value, './');

        if (strlen($value) > static::MAX_PATH_LENGTH) {
            $value = substr($value, 0, static::MAX_PATH_LENGTH);
            $truncated = true;
        }

        return $value;
    }

    protected function safeString(string $value, bool &$truncated): string
    {
        if (str_contains($value, "\0")
            || stripos($value, '<?php') !== false
            || stripos($value, '<script') !== false
            || stripos($value, 'javascript:') !== false
            || preg_match('/^(O|a):\d+:/', $value)) {
            throw new InvalidArgumentException('Unsafe activity metadata value.');
        }

        if (strlen($value) > static::MAX_METADATA_STRING) {
            $value = substr($value, 0, static::MAX_METADATA_STRING);
            $truncated = true;
        }

        return $value;
    }

    protected function nullableAlias(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!preg_match('/^[a-z][a-z0-9_]*$/', $value) || strlen($value) > 191) {
            throw new InvalidArgumentException('Invalid activity type alias.');
        }

        return $value;
    }

    protected function nullableId($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_scalar($value)) {
            throw new InvalidArgumentException('Invalid activity identifier.');
        }

        $value = (string) $value;

        if (strlen($value) > 191) {
            throw new InvalidArgumentException('Activity identifier is too long.');
        }

        return $value;
    }

    protected function nullableResource(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!preg_match('/^[A-Za-z0-9_-]+$/', $value) || strlen($value) > 191) {
            throw new InvalidArgumentException('Invalid activity resource.');
        }

        return $value;
    }

    protected function source(?string $value): string
    {
        $value = $value ?: 'admin';

        if (!in_array($value, static::SOURCES, true)) {
            throw new InvalidArgumentException('Invalid activity source.');
        }

        return $value;
    }

    protected function ip(?string $value): ?string
    {
        return $value && filter_var($value, FILTER_VALIDATE_IP) ? $value : null;
    }

    protected function routeName(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (str_contains($value, '://') || str_contains($value, '?') || strlen($value) > 191) {
            throw new InvalidArgumentException('Invalid activity route name.');
        }

        return $value;
    }

    protected function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
