<?php

namespace TCG\Voyager\Activity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ActivityRecorder
{
    protected static bool $writingFallback = false;

    public function __construct(
        protected ActivityContext $context,
        protected ActivitySanitizer $sanitizer,
        protected ActivityWriter $writer
    ) {
    }

    public function enabled(): bool
    {
        return (bool) config('voyager-activity.enabled', true);
    }

    public function record(ActivityEvent $event, bool $safe = false): ?string
    {
        if (!$this->enabled()) {
            return null;
        }

        if ($event->resource !== null && in_array($event->resource, (array) config('voyager-activity.excluded_resources', []), true)) {
            return null;
        }

        try {
            $uuid = $event->eventUuid ?: (string) Str::uuid();
            $event->eventUuid = $uuid;
            $event->actorType ??= $this->context->actorType();
            $event->actorId ??= $this->context->actorId();
            $event->source ??= $this->context->source() ?: $this->detectSource();
            $event->requestId ??= $this->context->requestId();
            $event->operationId ??= $this->context->operationId();
            $event->ipAddress ??= $this->context->ipAddress();
            $event->routeName ??= $this->context->routeName();

            $row = $this->sanitizer->sanitize($event);
            $this->writer->write($row, $uuid);

            return $uuid;
        } catch (Throwable $e) {
            if ($safe) {
                $this->writeFallback($event, $e);

                return null;
            }

            throw $e;
        }
    }

    public function recordSafe(ActivityEvent $event): ?string
    {
        return $this->record($event, true);
    }

    public function recordEvent(
        string $event,
        string $result = 'success',
        $subject = null,
        ?string $resource = null,
        array $changedFields = [],
        array $metadata = [],
        ?string $subjectType = null,
        ?string $subjectId = null
    ): ?string {
        if ($subject instanceof Model) {
            $subjectType = $subjectType ?: ActivitySubject::forModel($subject);
            $subjectId = $subjectId ?: (string) $subject->getKey();
        }

        return $this->record(new ActivityEvent(
            static::categoryFor($event),
            $event,
            $result,
            $subjectType,
            $subjectId,
            $resource,
            $changedFields,
            $metadata
        ));
    }

    public function recordEventSafe(
        string $event,
        string $result = 'success',
        $subject = null,
        ?string $resource = null,
        array $changedFields = [],
        array $metadata = [],
        ?string $subjectType = null,
        ?string $subjectId = null
    ): ?string {
        if ($subject instanceof Model) {
            $subjectType = $subjectType ?: ActivitySubject::forModel($subject);
            $subjectId = $subjectId ?: (string) $subject->getKey();
        }

        return $this->recordSafe(new ActivityEvent(
            static::categoryFor($event),
            $event,
            $result,
            $subjectType,
            $subjectId,
            $resource,
            $changedFields,
            $metadata
        ));
    }

    public static function categoryFor(string $event): string
    {
        $prefix = Str::before($event, '.');

        return match ($prefix) {
            'media' => 'media',
            'auth' => 'auth',
            'access' => 'access',
            'user', 'role', 'setting', 'bread', 'menu' => 'configuration',
            default => 'content',
        };
    }

    public static function registerEvent(string $event, array $allowedMetadataKeys = []): void
    {
        ActivitySanitizer::register($event, $allowedMetadataKeys);
    }

    public function operationId(): string
    {
        return $this->context->operationId();
    }

    public function beginOperation(): string
    {
        return $this->context->beginOperation();
    }

    public function context(): ActivityContext
    {
        return $this->context;
    }

    protected function detectSource(): string
    {
        if (app()->runningInConsole()) {
            return 'console';
        }

        return request()->is('api/*') ? 'api' : 'admin';
    }

    protected function writeFallback(ActivityEvent $event, Throwable $error): void
    {
        if (static::$writingFallback) {
            return;
        }

        static::$writingFallback = true;

        try {
            $path = storage_path('logs/voyager-activity-fallback.log');
            $logger = Log::build([
                'driver' => 'daily',
                'path'   => $path,
                'days'   => (int) config('voyager-activity.fallback_log_days', 7),
                'level'  => 'warning',
            ]);

            $logger->warning('voyager_activity.write_failed', [
                'event'        => $event->event,
                'category'     => $event->category,
                'result'       => $event->result,
                'subject_type' => $event->subjectType,
                'subject_id'   => $event->subjectId,
                'error'        => get_class($error),
            ]);
        } catch (Throwable $ignored) {
            // The activity journal must never turn a failed write into a fatal error.
        } finally {
            static::$writingFallback = false;
        }
    }
}
