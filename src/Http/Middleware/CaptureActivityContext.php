<?php

namespace TCG\Voyager\Http\Middleware;

use Closure;
use TCG\Voyager\Activity\ActivityContext;

class CaptureActivityContext
{
    public function handle($request, Closure $next)
    {
        if (config('voyager-activity.enabled', true)) {
            $context = app(ActivityContext::class);
            $context->reset();
            $context->capture($request);
        }

        return $next($request);
    }
}
