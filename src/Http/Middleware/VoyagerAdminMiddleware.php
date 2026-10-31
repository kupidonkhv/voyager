<?php

namespace TCG\Voyager\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Activity\ActivityEvent;
use TCG\Voyager\Activity\ActivityRecorder;
use TCG\Voyager\Activity\ActivitySubject;

class VoyagerAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        auth()->setDefaultDriver(app('VoyagerGuard'));

        if (!Auth::guest()) {
            $user = Auth::user();
            app()->setLocale($user->locale ?? app()->getLocale());

            if ($user->hasPermission('browse_admin')) {
                return $next($request);
            }

            if (config('voyager-activity.enabled', true)) {
                $routeName = $request->route()?->getName();
                $resource = ActivitySubject::resourceFromRoute($routeName);

                app(ActivityRecorder::class)->recordSafe(new ActivityEvent(
                    'access',
                    'access.denied',
                    'denied',
                    $resource ? \Illuminate\Support\Str::singular($resource) : null,
                    null,
                    $resource,
                    [],
                    []
                ));
            }

            return redirect('/');
        }

        $urlLogin = route('voyager.login');

        return redirect()->guest($urlLogin);
    }
}
