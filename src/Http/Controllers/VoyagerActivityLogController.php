<?php

namespace TCG\Voyager\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use TCG\Voyager\Activity\ActivitySanitizer;
use TCG\Voyager\Activity\ActivitySubjectResolver;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Models\ActivityLog;

class VoyagerActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('browse_activity_logs');

        $filters = $this->filters($request);
        $query = ActivityLog::query();

        if ($filters['from']) {
            $query->where('occurred_at', '>=', $filters['from']);
        }

        if ($filters['to']) {
            $query->where('occurred_at', '<=', $filters['to']);
        }

        foreach (['category', 'event', 'result', 'resource', 'subject_type', 'subject_id', 'operation_id'] as $field) {
            if ($filters[$field] !== null) {
                $query->where($field, $filters[$field]);
            }
        }

        if ($filters['actor_id'] !== null) {
            $query->where('actor_id', $filters['actor_id']);
        }

        $pageSize = min(
            max(1, (int) config('voyager-activity.max_page_size', 100)),
            max(1, (int) $request->input('per_page', config('voyager-activity.page_size', 50)))
        );

        $logs = $query
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->withQueryString();

        $events = ActivitySanitizer::eventCodes();
        $categories = ActivitySanitizer::categories();
        $results = ActivitySanitizer::results();

        return Voyager::view('voyager::activity.index', compact(
            'logs',
            'filters',
            'events',
            'categories',
            'results',
            'pageSize'
        ));
    }

    public function show($id, ActivitySubjectResolver $resolver)
    {
        $this->authorize('browse_activity_logs');

        $log = ActivityLog::findOrFail($id);
        $user = Auth::guard(app('VoyagerGuard'))->user();
        $canViewContext = $user ? $user->can('browse_activity_log_context') : false;

        $deletionEvents = [
            'record.deleted', 'record.force_deleted', 'gallery.deleted',
            'user.deleted', 'role.deleted', 'setting.deleted',
            'menu.item_deleted', 'bread.deleted',
        ];

        $subjectLabel = in_array($log->event, $deletionEvents, true)
            ? null
            : $resolver->label($log, $user);

        return Voyager::view('voyager::activity.show', compact(
            'log',
            'canViewContext',
            'subjectLabel'
        ));
    }

    protected function filters(Request $request): array
    {
        $from = $this->date($request->input('from'), false);
        $to = $this->date($request->input('to'), true);

        return [
            'from'         => $from,
            'to'           => $to,
            'actor_id'     => $this->string($request->input('actor_id')),
            'category'     => $this->in($request->input('category'), ActivitySanitizer::categories()),
            'event'        => $this->in($request->input('event'), ActivitySanitizer::eventCodes()),
            'result'       => $this->in($request->input('result'), ActivitySanitizer::results()),
            'resource'     => $this->pattern($request->input('resource'), '/^[A-Za-z0-9_-]+$/'),
            'subject_type' => $this->pattern($request->input('subject_type'), '/^[a-z][a-z0-9_]*$/'),
            'subject_id'   => $this->string($request->input('subject_id')),
            'operation_id' => $this->uuid($request->input('operation_id')),
        ];
    }

    protected function date($value, bool $endOfDay): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            $date = Carbon::parse($value, config('app.timezone', 'UTC'))->utc();

            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function string($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Str::limit((string) $value, 191, '');
    }

    protected function in($value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }

    protected function pattern($value, string $pattern): ?string
    {
        return is_string($value) && $value !== '' && preg_match($pattern, $value) ? $value : null;
    }

    protected function uuid($value): ?string
    {
        return is_string($value) && Str::isUuid($value) ? $value : null;
    }
}
