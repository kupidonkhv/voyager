<?php

namespace TCG\Voyager\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use TCG\Voyager\Activity\ActivityEvent;
use TCG\Voyager\Activity\ActivityRecorder;
use TCG\Voyager\Activity\ActivitySubject;

class RecordAccessDenied
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (AuthorizationException|AccessDeniedHttpException $e) {
            $this->record($request);
            throw $e;
        }
    }

    protected function record($request): void
    {
        if (!config('voyager-activity.enabled', true)) {
            return;
        }

        $routeName = $request->route()?->getName();
        $resource = ActivitySubject::resourceFromRoute($routeName);
        $subjectId = $request->route('id');

        if (!is_scalar($subjectId)) {
            $subjectId = null;
        }

        app(ActivityRecorder::class)->recordSafe(new ActivityEvent(
            'access',
            'access.denied',
            'denied',
            $resource ? Str::singular($resource) : null,
            $subjectId,
            $resource,
            [],
            []
        ));
    }
}
